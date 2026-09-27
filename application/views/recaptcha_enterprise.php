<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google reCAPTCHA Enterprise</title>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($site_key); ?>"></script>
    <style>
        body {
            margin: 0;
            background: #f5f7fa;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }

        .recaptcha-card {
            max-width: 560px;
            margin: 80px auto;
            padding: 32px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .recaptcha-card h1 {
            margin-top: 0;
            font-size: 26px;
        }

        .recaptcha-card p {
            line-height: 1.6;
        }

        .recaptcha-card button {
            padding: 11px 18px;
            border: 0;
            border-radius: 4px;
            background: #1669d9;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
        }

        .recaptcha-card button:disabled {
            cursor: wait;
            opacity: .65;
        }

        #recaptcha-status {
            min-height: 24px;
            margin-top: 18px;
            font-weight: bold;
        }

        .status-success { color: #167a3d; }
        .status-error { color: #b42318; }
    </style>
</head>
<body>
    <main class="recaptcha-card">
        <h1>Google reCAPTCHA Enterprise</h1>
        <p>
            Use this page to generate a reCAPTCHA Enterprise token for the
            <strong><?php echo html_escape($recaptcha_action); ?></strong> action.
        </p>

        <form id="recaptcha-form" method="post">
            <input type="hidden" id="recaptcha-token" name="recaptcha_token" value="">
            <input type="hidden" name="recaptcha_action" value="<?php echo html_escape($recaptcha_action); ?>">
            <button id="recaptcha-submit" type="submit">Generate token</button>
        </form>

        <div id="recaptcha-status" role="status" aria-live="polite"></div>
    </main>

    <script>
        (function () {
            'use strict';

            var siteKey = <?php echo json_encode($site_key); ?>;
            var action = <?php echo json_encode($recaptcha_action); ?>;
            var form = document.getElementById('recaptcha-form');
            var button = document.getElementById('recaptcha-submit');
            var tokenInput = document.getElementById('recaptcha-token');
            var status = document.getElementById('recaptcha-status');
            var verifyUrl = <?php echo json_encode(site_url('recaptcha-enterprise/verify')); ?>;

            function verifyToken(token) {
                var request = new XMLHttpRequest();
                request.open('POST', verifyUrl, true);
                request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

                request.onreadystatechange = function () {
                    var response;

                    if (request.readyState !== 4) {
                        return;
                    }

                    try {
                        response = JSON.parse(request.responseText);
                    } catch (error) {
                        response = {};
                    }

                    if (request.status === 200 && response.success) {
                        status.className = 'status-success';
                        status.textContent = 'Token verified. Risk score: ' + response.score;
                    } else {
                        status.className = 'status-error';
                        status.textContent = response.message || 'Unable to verify the token.';
                    }

                    button.disabled = false;
                };

                request.onerror = function () {
                    status.className = 'status-error';
                    status.textContent = 'Unable to contact the verification endpoint.';
                    button.disabled = false;
                };

                request.send('recaptcha_token=' + encodeURIComponent(token));
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                button.disabled = true;
                tokenInput.value = '';
                status.className = '';
                status.textContent = 'Generating token...';

                grecaptcha.enterprise.ready(function () {
                    grecaptcha.enterprise.execute(siteKey, { action: action })
                        .then(function (token) {
                            tokenInput.value = token;
                            status.textContent = 'Verifying token...';
                            verifyToken(token);
                        })
                        .catch(function () {
                            status.className = 'status-error';
                            status.textContent = 'Unable to generate a token. Check the site key and allowed domains.';
                            button.disabled = false;
                        });
                });
            });
        }());
    </script>
</body>
</html>
