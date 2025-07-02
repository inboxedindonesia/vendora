<?php
namespace Opencart\Admin\Model\Tenant;

class Ticket extends \Opencart\System\Engine\Model {
    public function getTicketsByCompany(int $company_id): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "tenant_ticket` WHERE company_id = '" . (int)$company_id . "' ORDER BY date_added DESC";
        return $this->db->query($sql)->rows ?? [];
    }
    public function addTicket(int $company_id, array $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "tenant_ticket` SET
            company_id = '" . (int)$company_id . "',
            subject = '" . $this->db->escape($data['subject']) . "',
            message = '" . $this->db->escape($data['message']) . "',
            status = 'open',
            date_added = NOW()");
    }
    public function getTicket(int $ticket_id): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "tenant_ticket` WHERE ticket_id = '" . (int)$ticket_id . "'";
        return $this->db->query($sql)->row ?? [];
    }
    public function editTicket(int $ticket_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "tenant_ticket` SET
            subject = '" . $this->db->escape($data['subject']) . "',
            message = '" . $this->db->escape($data['message']) . "'
            WHERE ticket_id = '" . (int)$ticket_id . "'");
    }
    public function deleteTicket(int $ticket_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "tenant_ticket` WHERE ticket_id = '" . (int)$ticket_id . "'");
    }
} 