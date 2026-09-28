<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\RiskAnalysis\ClassificationReason;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;

class Google_recaptcha
{
    private $autoload_available = false;

    public function __construct()
    {
        $autoload_file = APPPATH.'third_party/google_api/autoload.php';

        if (is_readable($autoload_file)) {
            require_once $autoload_file;
            $this->autoload_available = true;
        } else {
            log_message('error', 'Google API autoloader was not found at '.$autoload_file);
        }
    }

    /** TRUE when the site key, project and credentials are all configured. */
    public function is_configured()
    {
        return RECAPTCHA_ENTERPRISE_SITE_KEY !== ''
            && RECAPTCHA_ENTERPRISE_PROJECT_ID !== ''
            && RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE !== '';
    }

    /**
     * Verify a one-use token from a public form: valid, for the expected
     * action, and scored at least $min_score.
     *
     * Without reCAPTCHA configured, development accepts the form (so it can
     * be tested locally) and every other environment rejects it.
     */
    public function verify($token, $action, $min_score = 0.5, $ip_address = '', $user_agent = '')
    {
        if (!$this->is_configured()) {
            if (ENVIRONMENT === 'development') {
                log_message('debug', 'reCAPTCHA Enterprise is not configured; '.$action.' accepted in development.');
                return true;
            }

            log_message('error', 'reCAPTCHA Enterprise is not configured; '.$action.' rejected.');
            return false;
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $result = $this->create_assessment($token, $action, $ip_address, $user_agent);
        if (empty($result['success'])) {
            log_message(
                'error',
                $action.' reCAPTCHA Enterprise verification failed: '.$result['message']
            );
            return false;
        }

        $score = isset($result['score']) ? (float) $result['score'] : 0.0;
        if ($score < $min_score) {
            log_message('info', $action.' reCAPTCHA Enterprise rejected a score of '.$score.'.');
            return false;
        }

        return true;
    }

    public function create_assessment($token, $action, $ip_address = '', $user_agent = '')
    {
        if (!$this->autoload_available) {
            return $this->result(false, 503, 'The Google API library is not installed.');
        }

        if (!is_readable(RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE)) {
            log_message('error', 'The reCAPTCHA Enterprise credentials file is not readable.');
            return $this->result(false, 503, 'The reCAPTCHA Enterprise credentials are not configured.');
        }

        $client = null;

        try {
            $client = new RecaptchaEnterpriseServiceClient(array(
                'transport' => 'rest',
                'credentials' => RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE
            ));

            $event = (new Event())
                ->setSiteKey(RECAPTCHA_ENTERPRISE_SITE_KEY)
                ->setToken($token)
                ->setExpectedAction($action);

            if ($ip_address !== '') {
                $event->setUserIpAddress($ip_address);
            }
            if ($user_agent !== '') {
                $event->setUserAgent($user_agent);
            }

            $assessment = (new Assessment())->setEvent($event);
            $request = (new CreateAssessmentRequest())
                ->setParent(RecaptchaEnterpriseServiceClient::projectName(RECAPTCHA_ENTERPRISE_PROJECT_ID))
                ->setAssessment($assessment);

            $response = $client->createAssessment($request);
            $token_properties = $response->getTokenProperties();

            if (!$token_properties->getValid()) {
                return $this->result(false, 422, 'The reCAPTCHA Enterprise token is invalid.', array(
                    'invalid_reason' => $this->enum_name(
                        InvalidReason::class,
                        $token_properties->getInvalidReason()
                    )
                ));
            }

            $verified_action = $token_properties->getAction();
            if (!hash_equals($action, $verified_action)) {
                return $this->result(false, 422, 'The verified action does not match the expected action.', array(
                    'action' => $verified_action
                ));
            }

            $risk_analysis = $response->getRiskAnalysis();
            $reasons = array();
            foreach ($risk_analysis->getReasons() as $reason) {
                $reasons[] = $this->enum_name(ClassificationReason::class, $reason);
            }

            return $this->result(true, 200, 'The reCAPTCHA Enterprise assessment was created.', array(
                'score' => (float) $risk_analysis->getScore(),
                'action' => $verified_action,
                'reasons' => $reasons,
                'assessment_name' => $response->getName()
            ));
        } catch (Throwable $exception) {
            log_message('error', 'reCAPTCHA Enterprise assessment failed: '.$exception->getMessage());
            return $this->result(false, 502, 'Unable to create the reCAPTCHA Enterprise assessment.');
        } finally {
            if ($client !== null) {
                $client->close();
            }
        }
    }

    private function result($success, $status_code, $message, array $data = array())
    {
        return array_merge(array(
            'success' => (bool) $success,
            'status_code' => (int) $status_code,
            'message' => $message
        ), $data);
    }

    private function enum_name($enum_class, $value)
    {
        try {
            return $enum_class::name($value);
        } catch (UnexpectedValueException $exception) {
            return 'UNRECOGNIZED_'.$value;
        }
    }
}
