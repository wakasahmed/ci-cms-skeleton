<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_cron extends CI_Controller
{
    public function release_holds()
    {
        try {
            $this->load->model('Booking_model');
            $summary = $this->Booking_model->cancelAbandonedBookings();
            $emailsSent = $this->sendCancellationEmails($summary['cancelled_ids']);

            echo 'Abandoned bookings cancelled: ' . $summary['cancelled']
                . ', guide slots released: ' . $summary['released']
                . ', cancellation emails sent: ' . $emailsSent . PHP_EOL;
        } catch (Throwable $exception) {
            log_message('error', 'Abandoned booking cleanup failed: ' . $exception->getMessage());
            $this->failRun('Abandoned booking cleanup failed. See the application log.');
        }
    }

    /**
     * Ask customers to review their finished bookings (email template 14, both tour types).
     * Picks up to five Completed bookings whose date and slot end time have passed and whose
     * book_review_noti is still 'No', emails the customer, then sets book_review_noti to 'Yes'.
     * A booking whose email fails stays 'No' and is retried on a later run.
     */
    public function request_review()
    {
        $this->runNotification(
            'Review requests',
            'getBookingsForReviewRequest',
            'sendCustomerReviewRequest',
            'markReviewRequested'
        );
    }

    /**
     * Tell referrals they earned a commission (email template 13, both tour types). Same rules as
     * request_review, but for Completed bookings that came through a referral (book_ref_id > 0)
     * with a commission (book_ref_commission > 0) and book_commission_noti still 'No'. The
     * referral is emailed at their own address in their own language, then book_commission_noti
     * is set to 'Yes'. A booking whose email fails stays 'No' and is retried on a later run.
     */
    public function commission_earned()
    {
        $this->runNotification(
            'Commission notices',
            'getBookingsForCommissionNotice',
            'sendReferralCommissionEarned',
            'markCommissionNotified'
        );
    }

    /**
     * Shared run for the notification jobs: pick up to five bookings, email each one, then record
     * it. The method names are fixed by the calling job, never taken from the request.
     */
    private function runNotification($title, $selectMethod, $sendMethod, $markMethod)
    {
        try {
            $this->load->model('Booking_model');
            $this->load->library('Booking_email_service');

            $sent = 0;
            $failed = 0;
            foreach ($this->Booking_model->$selectMethod(5) as $bookingId) {
                if ($this->notifyBooking($title, $bookingId, $sendMethod, $markMethod)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }

            echo $title . ' sent: ' . $sent . ', failed: ' . $failed . PHP_EOL;
        } catch (Throwable $exception) {
            log_message('error', $title . ' run failed: ' . $exception->getMessage());
            $this->failRun($title . ' run failed. See the application log.');
        }
    }

    /** Send one notification email and record it. A failure is logged and never stops the run. */
    private function notifyBooking($title, $bookingId, $sendMethod, $markMethod)
    {
        try {
            if (!$this->booking_email_service->$sendMethod($bookingId)) {
                return false;
            }

            if (!$this->Booking_model->$markMethod($bookingId)) {
                log_message(
                    'error',
                    $title . ' email for booking ID ' . (int) $bookingId . ' was sent but could not be recorded.'
                );
            }

            return true;
        } catch (Throwable $exception) {
            log_message(
                'error',
                $title . ' email failed for booking ID ' . (int) $bookingId . ': ' . $exception->getMessage()
            );

            return false;
        }
    }

    /**
     * Email each customer whose booking was just cancelled (template 5 for tours, 6 for experiences).
     * Runs after the cancellations are committed, and a failed email never stops the others.
     *
     * @return int number of emails sent
     */
    private function sendCancellationEmails(array $bookingIds)
    {
        if (empty($bookingIds)) {
            return 0;
        }

        $this->load->library('Booking_email_service');
        $sent = 0;
        foreach ($bookingIds as $bookingId) {
            try {
                if ($this->booking_email_service->sendCustomerCancellation($bookingId)) {
                    $sent++;
                }
            } catch (Throwable $exception) {
                log_message(
                    'error',
                    'Booking cancellation email failed for booking ID ' . (int) $bookingId . ': '
                    . $exception->getMessage()
                );
            }
        }

        return $sent;
    }

    /** Report a failed run to whichever caller triggered it: cron on the CLI, or an HTTP request. */
    private function failRun($text)
    {
        $message = $text . PHP_EOL;

        if (is_cli()) {
            fwrite(STDERR, $message);
            exit(1);
        }

        set_status_header(500);
        echo $message;
    }
}
