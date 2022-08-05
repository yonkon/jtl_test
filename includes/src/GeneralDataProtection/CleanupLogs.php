<?php declare(strict_types=1);

namespace JTL\GeneralDataProtection;

/**
 * Class CleanupLogs
 * @package JTL\GeneralDataProtection
 *
 * Delete old logs containing personal data.
 * (interval former "interval_clear_logs" = 90 days)
 *
 * names of the tables, we manipulate:
 *
 * `temailhistory`
 * `tkontakthistory`
 * `tzahlungslog`
 * `tproduktanfragehistory`
 * `tverfuegbarkeitsbenachrichtigung`
 * `tjtllog`
 * `tzahlungseingang`
 * `tkundendatenhistory`
 * `tfloodprotect`
 */
class CleanupLogs extends Method implements MethodInterface
{
    /**
     * runs all anonymize routines
     */
    public function execute(): void
    {
        $this->cleanupEmailHistory();
        $this->cleanupContactHistory();
        $this->cleanupFloodProtect();
        $this->cleanupPaymentLogEntries();
        $this->cleanupProductInquiries();
        $this->cleanupAvailabilityInquiries();
        $this->cleanupLogs();
        $this->cleanupPaymentConfirmations();
        $this->cleanupCustomerDataHistory();
    }

    /**
     * delete email history
     * older than given interval
     */
    private function cleanupEmailHistory(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM temailhistory
                WHERE dSent <= :dateLimit
                ORDER BY dSent ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete customer history
     * older than given interval
     */
    private function cleanupContactHistory(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tkontakthistory
                WHERE dErstellt <= :dateLimit
                ORDER BY dErstellt ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete upload request history
     * older than given interval
     */
    private function cleanupFloodProtect(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tfloodprotect
                WHERE dErstellt <= :dateLimit
                ORDER BY dErstellt ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete log entries of payments
     * older than the given interval
     */
    private function cleanupPaymentLogEntries(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tzahlungslog
                WHERE dDatum <= :dateLimit
                ORDER BY dDatum ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete product inquiries of customers
     * older than the given interval
     */
    private function cleanupProductInquiries(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tproduktanfragehistory
                WHERE dErstellt <= :dateLimit
                ORDER BY dErstellt ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete availability demands of customers
     * older than the given interval
     */
    private function cleanupAvailabilityInquiries(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tverfuegbarkeitsbenachrichtigung
                WHERE dErstellt <= :dateLimit
                ORDER BY dErstellt ASC
                LIMIT :workLimit',
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete jtl log entries
     * older than the given interval
     */
    private function cleanupLogs(): void
    {
        $this->db->queryPrepared(
            "DELETE FROM tjtllog
                WHERE
                    (cLog LIKE '%@%' OR cLog LIKE '%kKunde%')
                    AND dErstellt <= :dateLimit
                ORDER BY dErstellt ASC
                LIMIT :workLimit",
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete payment confirmations of customers
     * not collected by 'wawi' and older than the given interval
     */
    private function cleanupPaymentConfirmations(): void
    {
        $this->db->queryPrepared(
            "DELETE FROM tzahlungseingang
                WHERE
                    cAbgeholt != 'Y'
                    AND dZeit <= :dateLimit
                ORDER BY dZeit ASC
                LIMIT :workLimit",
            [
                'dateLimit' => $this->dateLimit,
                'workLimit' => $this->workLimit
            ]
        );
    }

    /**
     * delete customer data history
     * CONSIDER: using no time base or limit here!
     *
     * (§76 BDSG Abs(4) : "Die Protokolldaten sind am Ende des auf deren Generierung folgenden Jahres zu löschen.")
     */
    private function cleanupCustomerDataHistory(): void
    {
        $this->db->queryPrepared(
            'DELETE FROM tkundendatenhistory
                WHERE dErstellt < MAKEDATE(YEAR(:nowTime) - 1, 1)
                ORDER BY dErstellt ASC
                LIMIT :workLimit',
            [
                'nowTime'   => $this->now->format('Y-m-d H:i:s'),
                'workLimit' => $this->workLimit
            ]
        );
    }
}
