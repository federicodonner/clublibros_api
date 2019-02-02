<?php

class db{

  // Properties
  private $dbhost = 'localhost';
  private $dbuser = 'feder161_cladmin';
  private $dbpass = 'cladmin';
  private $dbname = 'feder161_clublibros';

//MAMP
  // private $dbhost = 'localhost';
  // private $dbuser = 'root';
  // private $dbpass = 'root';
  // private $dbname = 'feder161_clublibros';

  public function connect(){
    $mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname;charset=UTF8";
    $dbConnection = new PDO($mysql_connect_str, $this->dbuser, $this->dbpass);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbConnection;
  }
}
