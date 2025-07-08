<?php
interface IStorage {
    public function load();
    public function save($data);
}
