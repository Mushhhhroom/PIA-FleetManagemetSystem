<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMergeAndDriverAvailabilityFields extends Migration
{
    public function up()
    {
        // 1. Add ticket_id, is_merged, and merged_with_request_id to trip_requests
        $fields = $this->db->getFieldNames('trip_requests');
        if (!in_array('ticket_id', $fields, true)) {
            $this->db->query("ALTER TABLE `trip_requests` 
                ADD COLUMN `ticket_id` INT UNSIGNED NULL AFTER `status`,
                ADD COLUMN `is_merged` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ticket_id`,
                ADD COLUMN `merged_with_request_id` INT UNSIGNED NULL AFTER `is_merged`,
                ADD INDEX (`ticket_id`),
                ADD INDEX (`merged_with_request_id`)");
        }

        // 2. Add merged_request_ids to trip_tickets
        $ticketFields = $this->db->getFieldNames('trip_tickets');
        if (!in_array('merged_request_ids', $ticketFields, true)) {
            $this->db->query("ALTER TABLE `trip_tickets` 
                ADD COLUMN `merged_request_ids` TEXT NULL AFTER `request_id`");
        }

        // 3. Backfill existing trip_requests.ticket_id from existing trip_tickets
        $this->db->query("UPDATE `trip_requests` tr 
            INNER JOIN `trip_tickets` tt ON tt.request_id = tr.id 
            SET tr.ticket_id = tt.id 
            WHERE tr.ticket_id IS NULL");
    }

    public function down()
    {
        $fields = $this->db->getFieldNames('trip_requests');
        if (in_array('ticket_id', $fields, true)) {
            $this->db->query("ALTER TABLE `trip_requests` 
                DROP COLUMN `is_merged`,
                DROP COLUMN `merged_with_request_id`,
                DROP COLUMN `ticket_id`");
        }

        $ticketFields = $this->db->getFieldNames('trip_tickets');
        if (in_array('merged_request_ids', $ticketFields, true)) {
            $this->db->query("ALTER TABLE `trip_tickets` DROP COLUMN `merged_request_ids`");
        }
    }
}
