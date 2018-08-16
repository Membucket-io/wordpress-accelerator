<?php
  class Well {
    public $ID;
    public $Name;
    public $Running;
    
    public function __construct( $id, $name, $running ) {
      $this->ID = $id;
      $this->Name = $name;
      $this->Running = $running;
    }
  }
