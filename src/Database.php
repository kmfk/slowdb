<?php

namespace SlowDB;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Finder\Finder;

class Database
{
    /**
     * The filepath location where database files
     * should be kept
     *
     * @var string
     */
    private $filepath;

    /**
     * The Database Collections
     *
     * @var ArrayCollection
     */
    private $collections;

    /**
     * Constructor
     */
    public function __construct($filepath = '/tmp/slowdb')
    {
        if (!file_exists($filepath)) {
            mkdir($filepath);
        }

        $this->filepath    = $filepath;
        $this->collections = $this->loadCollections();
    }

    /**
     * Magic getter - retrieves a collection by name
     * Collections are idempotent, we create a collection if it does not exist
     *
     * @param  string $name The name of the Collection
     *
     * @return Collection
     */
    public function __get($name)
    {
        $name = strtolower($name);

        if ($this->collections->containsKey($name)) {
            return $this->collections->get($name);
        }

        $this->collections->set($name, $this->createCollection($name));

        return $this->collections->get($name);
    }

    /**
     * Lists the available Collections by name
     *
     * @return array
     */
    public function all()
    {
        return $this->collections->getKeys();
    }

    /**
     * Drops a Collection - removing all data
     *
     * @param  string $collection  The name of the Collection to drop
     */
    public function drop($collection)
    {
        $this->collections->get($collection)->drop();
        $this->collections->remove($collection);

        return true;
    }

    /**
     * Drops all the Collection in the Database
     */
    public function dropAll()
    {
        foreach ($this->collections->getKeys() as $collection) {
            $this->dropCollection($collection);
        }
    }

    /**
     * Loads the collections for the Database
     *
     * @return ArrayCollection
     */
    private function loadCollections()
    {
        $collections = new ArrayCollection();

        $finder = new Finder();
        $finder->files()->name('*.dat')->in($this->filepath);

        foreach ($finder as $file) {
            $name = $file->getBasename('.dat');
            $collections->set($name, $this->createCollection($name));
        }

        return $collections;
    }

    /**
     * Creates a new Collection
     *
     * @param  string $name The name of the Collection
     *
     * @return Collection
     */
    private function createCollection($name)
    {
        return new Collection($name, $this->getCollectionFilePath($name));
    }

    /**
     * Generates a new database file for a collection based on the
     * default database location
     *
     * @param  string $collection The name of the Collection
     *
     * @return string
     */
    private function getCollectionFilePath($collection)
    {
        return sprintf($this->filepath . '/%s.dat', strtolower($collection));
    }
}