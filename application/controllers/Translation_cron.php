<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Translation_cron extends CI_Controller
{
    public function process($limit = 25)
    {
        // if (!is_cli())
        // {
        //     show_error('Translation jobs may only be processed from the command line.', 403);
        //     return;
        // }

        $limit = ctype_digit((string) $limit) ? max(1, min(100, (int) $limit)) : 25;
        try
        {
            $this->load->library('manage_translation_service');
            $summary = $this->manage_translation_service->process_pending($limit);
            echo 'Translation jobs: claimed='.$summary['claimed'].' succeeded='.$summary['succeeded'].' failed='.$summary['failed'].' skipped='.$summary['skipped'].' stale_released='.$summary['released'].PHP_EOL;
        }
        catch (Throwable $exception)
        {
            log_message('error', 'Translation cron failed: '.trim(strip_tags($exception->getMessage())));
            fwrite(STDERR, 'Translation jobs could not be processed. See the application log.'.PHP_EOL);
            exit(1);
        }
    }
}

