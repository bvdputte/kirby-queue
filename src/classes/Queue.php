<?php

namespace bvdputte\kirbyQueue;
use Kirby\Data\Yaml;
use Kirby\Toolkit\F;
use Kirby\Cms\Dir;
use Kirby\Toolkit\Collection;

class Queue
{
    public $name;
    private $handler;
    private $current_job;

    function __construct($name, $handler) {
        $this->name = $name;
        $this->handler = $handler;
    }

    /**
     * Adds a new job to the queue
     * @param string  Name of the action to be performed
     * @param mixed   Any data you want to pass in
     */
    public function addJob($data = null)
    {
        $id = uniqid();

        $jobfile = $this->_getFolderPath() . DS . $id . '.yml';

        yaml::write($jobfile, [
            'id' => $id,
            'added' => date('c'),
            //'name' => $name,
            'data' => $data
        ]);
    }

    /**
     * Adds a failed job back to the queue
     * @param  string|Job  ID or Job object to be retried
     * @throws Error       When a non-Job object is given
     * @throws Error       When failed Job with ID is not found
     */
    public static function restoreFailedJob($failedJob)
    {
        if (is_string($failedJob)) {
            $failedJob = $this->_findFailedJobById($failedJob);
        }

        if (!is_a($failedJob, 'Job')) {
            throw new \Error('`restoreFailedJob()` expects a Job object');
        }

        f::move(
            $this->_getFailedFolderPath() . DS . $failedJob->id() . '.yml',
            $this->_getFolderPath()       . DS . $failedJob->id() . '.yml'
        );
    }

    /**
     * Removes a failed job
     * @param  string|Job  ID or Job object to be deleted
     * @throws Error       When a non-Job object is given
     * @throws Error       When failed Job with ID is not found
     */
    public function removeFailedJob($failedJob)
    {
        if (is_string($failedJob)) {
            $failedJob = $this->_findFailedJobById($failedJob);
        }

        if (!is_a($failedJob, 'Job')) {
            throw new \Error('`removeFailedJob` expects a Job object');
        }

        f::remove($this->_getFailedFolderPath() . DS . $failedJob->id() . '.yml');
    }

    /**
     * Changes a job to failed by moving it to the failed folder
     * @param  Job  Job object to be moved to failed
     */
    private function _failJob($job, $error) {
        $jobfile = $this->_getFailedFolderPath() . DS . $job->id() . '.yml';

        yaml::write($jobfile, [
            'id' => $job->id(),
            'added' => $job->added(),
            'name' => $job->name(),
            'data' => $job->data(),
            'error' => $error,
            'tried' => date('c')
        ]);
    }

    /**
     * Executes the first job in the queue folder
     */
    public function work() {
        // Protect ourselfs against multiple workers at once
        if ($this->_isWorking()) exit();

        $this->_prepareBeforeWork();

        register_shutdown_function(function(){
            if($this->current_job) {
                $this->_failJob($this->current_job, 'Job action terminated execution');
                $this->_cleanUpAfterWork();
            }
        });

        if ($this->hasJobs()) {
            $this->current_job = $this->_getNextJob();
            $currJob = $this->current_job;
            try {
                if (!is_callable($this->handler)) {
                    throw new \Error('Handler for "' . $currJob->name() . '" is not defined');
                }
                if (call_user_func($this->handler, $currJob) === false) {
                    throw new \Error('Job returned false');
                }
            } catch (\Exception $e) {
                $this->_failJob($currJob, $e->getMessage());
            } catch (\Error $e) {
                $this->_failJob($currJob, $e->getMessage());
            }
        }

        $this->_cleanUpAfterWork();
    }

    private function _getJobs($failed)
    {
        $path = $this->_getFolderPath();
        if($failed) $path = $this->_getFailedFolderPath();

        $jobs = dir::read($path);

        $jobs = array_filter($jobs, function($job) {
            return substr($job,0,1) != '.';
        });

        $jobs = array_map(function ($job) use ($path) {
            return new Job(yaml::read($path . DS . $job));
        }, $jobs);

        return new Collection($jobs);
    }

    /**
     * Returns all jobs in the queue
     * @return Collection  Collection with Job objects
     */
    public function getJobs() {
        return $this->_getJobs(false);
    }

    /**
     * Returns all failed jobs
     * @return Collection  Collection with Job objects
     */
    public function getFailedJobs() {
        return $this->_getJobs(true);
    }

    /**
     * Checks if the queue has jobs left to work on
     * @return boolean
     */
    public function hasJobs()
    {
        return $this->_getNextJobfile() !== false;
    }

    /**
     * Removes all jobs from the queue, including failed jobs
     */
    public function flush() {
        dir::clean($this->_getFolderPath());
    }

    /**
     * Returns a failed Job by ID
     * @return Job  The failed Job with the matching ID
     * @throws Error  When failed Job with ID is not found
     */
    private function _findFailedJobById($id) {
        $filename = $this->_getFailedFolderPath() . DS . $id . '.yml';

        if (!f::exists($filename)) throw new \Error('Job not found');

        return new Job(yaml::read($filename));

    }

    /**
     * Returns the filename of the next job in the queue
     * @return String  The filename of the first job in the queue
     * @return Bool  If no files found in queue, returns false
     */
    private function _getNextJobFile() {
        foreach(dir::read($this->_getFolderPath()) as $jobfile) {
            // No .working or .DS_store
            if (substr($jobfile,0,1) == '.') continue;

            // Return first jobfile we find
            return $jobfile;
        }

        return false;
    }

    /**
     * Returns the Job object of the next job in the queue
     * @return Job  The Job object of the first job in the queue
     * @return Bool  If no files found in queue, returns false
     */
    private function _getNextJob()
    {
        $jobfile = $this->_getNextJobFile();

        $job = yaml::read($this->_getFolderPath() . DS . $jobfile);
        f::remove($this->_getFolderPath() . DS . $jobfile);

        return new Job($job);
    }

    /**
     * Returns the full path of the queue folder
     * @return string
     */
    private function _getFolderPath() {
        $root = kirby()->option("bvdputte.kirbyqueue.root");
        return kirby()->roots()->site() . DS . $root . DS . $this->name;
    }

    /**
     * Returns the full path of the failed jobs folder
     * @return string
     */
    private function _getFailedFolderPath() {
        return $this->_getFolderPath() . DS . '.failed';
    }

    /**
     * Create "the subfolder that holds the job that is being processed"
     */
    private function _createWorkingFolderPath() {
        dir::make($this->_getFolderPath() . DS . '.working');
    }

    /**
     * Removes the "subfolder that holds the job that is being processed"
     */
    private function _removeWorkingFolderPath() {
        dir::remove($this->_getFolderPath() . DS . '.working');
    }

    /**
     * Checks if the "subfolder that holds the job that is being processed" exists
     * @return bool
     */
    private function _isWorking() {
        return f::exists($this->_getFolderPath() . DS . '.working');
    }

    /**
     * Prepare environment before work
     */
    private function _prepareBeforeWork() {
        $this->_createWorkingFolderPath();
    }

    /**
     * Clean up after work
     */
    private function _cleanUpAfterWork() {
        $this->current_job = null;
        $this->_removeWorkingFolderPath();
    }
}