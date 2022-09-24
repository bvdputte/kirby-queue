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
    private $currentJob;
    private $statuses = ["postponed", "failed"];

    function __construct($name, $handler) {
        $this->name = $name;
        $this->handler = $handler;
    }

    /**
     * Adds a new job to the queue
     * @param Job  Job object
     */
    public function addJob($job) {
        $job->write($this->_roots("root"));
    }

    /**
     * Removes a job from the queue
     * @param  string  Path to job file
     */
    public function deleteJob($job) {
        f::remove($job);
    }

    /**
     * Re-add a job back to the queue
     * @param  string  path to the job YAML file
     * @throws Error       When a non-Job object is given
     * @throws Error       When failed Job with ID is not found
     */
    public function restoreJob($path) {
        $file = basename($path);
        $typeDir = basename(dirname($path));
        // Jail inside the Queue root
        $path = $this->_roots("root") . '/' . $typeDir . '/' . $file;

        if(!f::exists($path)) throw new \Error('Job not found');

        f::move(
            $path,
            $this->_roots("root") . '/' . $file
        );
    }

    /**
     * Restore all postponed jobs in given folder back to the queue
     * @param  string  Path to folder
     */
    public function restoreJobs($type) {
        $dir = $this->_roots($type);
        $jobFiles = dir::read($dir);

        foreach($jobFiles as $jobFile) {
            $this->restoreJob($dir . '/' . $jobFile);
        }
    }

    public function workFirstJob() {
        // Protect ourselves against multiple workers at once
        if ($this->_isWorking()) exit();

        $this->_setWorking();

        register_shutdown_function(function(){
            if($this->currentJob) {
                $this->_changeJobStatus($this->currentJob, "failed", "Job action terminated execution");
                $this->_unsetWorking();
            }
        });

        if ($this->hasJobs()) {
            $this->currentJob = $this->_getNextJob();
            $currJob = $this->currentJob;
            f::remove($this->_roots("root") . '/' . $currJob->getId() . ".yml");

            $dueDate = $currJob->getDueDate();
            if ( isset($dueDate) && ($dueDate > time()) ) {
                // Postpone job
                $this->_changeJobStatus($currJob, "postponed");
            } else {
                // Job ok to be processed now
                try {
                    if (!is_callable($this->handler)) {
                        throw new \Error('Handler for "' . $this->name . '" is not defined');
                    }
                    if (call_user_func($this->handler, $currJob) === false) {
                        throw new \Error('Job "' . $currJob->getId() . '" returned false');
                    }
                } catch (\Exception $e) {
                    $this->_changeJobStatus($currJob, "failed", $e->getMessage());
                } catch (\Error $e) {
                    $this->_changeJobStatus($currJob, "failed", $e->getMessage());
                }
            }
        }

        $this->_unsetWorking();
    }

    /**
     * Returns all jobs in the queue
     * @param String  Optional type to filter the jobs
     * @return Collection  Collection with Job objects
     */
    public function getJobs($type = null) {
        if ( !isset($type) ) {
            return $this->_getJobs($this->_roots("root"));
        } else {
            $this->_getJobs($this->_roots($type));
        }
    }

    /**
     * Checks if the queue has jobs left to work on
     * @return boolean
     */
    public function hasJobs()
    {
        return $this->_getNextJob() !== false;
    }

    /**
     * Removes all jobs from the queue, including failed jobs
     */
    public function flush() {
        dir::clean($this->_roots("root"));
    }

    /**
     * Returns a failed Job by ID
     * @return Job  The failed Job with the matching ID
     * @throws Error  When failed Job with ID is not found
     */
    // private function _findFailedJobById($id) {
    //     $filename = $this->_roots("failed") . '/ . $id . '.yml';

    //     if (!f::exists($filename)) throw new \Error('Job not found');

    //     return new Job(yaml::read($filename));
    // }

    /**
     * Fetches all the jobs as Collection of Job from yaml files in a directory
     * @param String  Full path to directory to search for jobs
     * @return Collection  A Collection of Job objects, generated from the found YAML files in the dir
     */
    private function _getJobs($path)
    {
        $jobs = dir::read($path);

        $jobs = array_filter($jobs, function($jobFile) {
            return substr($jobFile,0,1) != '.';
        });

        $jobs = array_map(function ($jobFile) use ($path) {
            return Job::read($path . DS . $jobFile);
        }, $jobs);

        return new Collection($jobs);
    }

    /**
     * Returns the filename of the next job in the queue
     * @return String  The filename of the first job in the queue
     * @return Bool  If no files found in queue, returns false
     */
    private function _getNextJobFile() {
        $queueDir = $this->_roots("root");
        foreach(dir::read($queueDir) as $jobfile) {
            // No hidden files (.DS_store) or utility folder (.working, .failed, ...)
            if (substr($jobfile,0,1) == '.') continue;

            // Return first jobfile we find
            return $queueDir . '/' . $jobfile;
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

        if ($jobfile) {
            return Job::read($jobfile);
        } else {
            return false;
        }
    }

    /**
     * Changes a job's folder location to match his status
     * @param  Job  Job
     * @param  String  The status to change to
     * @param  String  Optional error message
     */
    private function _changeJobStatus($job, $status, $errorMessage = null) {
        $newPath = $this->_roots($status);

        // Add modification date
        $job->set("modified", date("c"));
        // If error, add error
        if( isset($errorMessage) ) {
            $job->set("errormsg", $errorMessage);
        }

        $job->write($newPath);
    }

    /**
     * Helper function to get the root of the current Queue to save the jobs
     * @return String  Path
     * @throws Error  When an not existing status has been requested
     */
    private function _roots($type) {
        $queueRoot = $this->_getRootFolder();

        if ($type == "root") {
            return $queueRoot;
        }

        if (in_array($type, $this->statuses)) {
            return $queueRoot . '/' . "." . $type;
        } else {
            throw new \Error('Status "' . $type . '" not found');
        }
    }

    /**
     * Helper function to get the queue plugin root folder
     * @return String  Path
     */
    private function _getRootFolder() {
        $root = kirby()->option("bvdputte.kirbyqueue.root");
        return kirby()->roots()->site() . '/' . $root . '/' . $this->name;
    }

    /**
     * Prepare environment before work
     */
    private function _setWorking() {
        f::write($this->_roots("root") . '/.working', time());
    }
    /**
     * Clean up environment before work
     */
    private function _unsetWorking() {
        $this->currentJob = null;
        f::remove($this->_roots("root") . '/.working');
    }
    /**
     * Checks if the "subfolder that holds the job that is being processed" exists
     * @return bool
     */
    private function _isWorking() {
        return f::exists($this->_roots("root") . '/.working');
    }
}
