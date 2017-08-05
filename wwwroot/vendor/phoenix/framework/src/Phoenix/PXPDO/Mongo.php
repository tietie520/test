<?php
namespace Phoenix\PXPDO;

interface Mongo {
    public function db();

    public function collections($_collections);
}