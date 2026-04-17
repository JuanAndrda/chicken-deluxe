<?php
/**
 * Base Model — all models extend this
 * Provides access to the Database singleton
 */
class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
