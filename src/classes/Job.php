<?php

namespace bvdputte\kirbyQueue;
use Kirby\Toolkit\Obj;
use Kirby\Data\Yaml;

class Job extends Obj
{
    private $id;
    private $added;
    public $dueDate;
    public $modified;
    public $errormsg;
    public $data;

    public function __construct() {
        $this->id = uniqid();
        $this->added = date('c');
    }

    /**
     * Get the ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the Due date
     */
    public function getDueDate() {
        return $this->dueDate;
    }

    /**
     * Set the data property
     * @param Array
     */
    public function data(Array $data) {
        $this->data = $data;
    }

    // General setter utility function
    public function set(String $prop, $value) {
        if (property_exists($this, $prop)) {
            $this->{$prop} = $value;
        }
    }

    /**
     * Set the due date
     * @param Integer  Timestamp
     */
    public function setDueDate(Int $time) {
        $this->dueDate = $time;
    }

    /**
     * Get variables from the data property
     * @param String\Array Property
     * @param Mixed Fallback (optional)
     */
    public function get($property, $fallback = null)
    {
        return isset($this->data[$property]) ? $this->data[$property] : $fallback;
    }

    /**
     * Serialize the Job to a YAML file on given path
     * @param String  Path
     */
    public function write(String $path) {
        $jobFileName = $path . '/' . $this->id . '.yml';

        $props = get_object_vars($this);
        yaml::write($jobFileName, $props);
    }

    /**
     * Unserialize a YAML file on given path to a Job
     * @param String  Path
     * @return Job  Job object with properties from YAML file
     */
    public static function read(String $path) {
        $job = new static();
        $props = yaml::read($path);

        foreach($props as $propName => $propValue) {
            $job->set($propName, $propValue);
        }

        return $job;
    }
}
