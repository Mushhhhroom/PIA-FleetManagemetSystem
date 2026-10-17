<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOnLeaveToDriversStatusEnum extends Migration
{
    public function up()
    {
        // Add 'on_leave' to drivers status ENUM
        $this->db->query("ALTER TABLE drivers MODIFY COLUMN status ENUM('available', 'on_trip', 'off_duty', 'on_leave', 'suspended') NOT NULL DEFAULT 'available'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE drivers MODIFY COLUMN status ENUM('available', 'on_trip', 'off_duty', 'suspended') NOT NULL DEFAULT 'available'");
    }
}
