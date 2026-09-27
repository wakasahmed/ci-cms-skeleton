<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #f3eee8;
            color: #2d2622;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        img {
            border: 0;
            display: block;
            height: auto;
            max-width: 100%;
        }

        .email-shell {
            width: 100%;
            background: #f3eee8;
        }

        .email-card {
            width: 100%;
            max-width: 680px;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
        }

        .email-shell-pad {
            padding: 24px;
        }

        .email-header {
            padding: 42px 54px 20px;
            text-align: center;
        }

        .brand-logo {
            margin: 0 auto;
            max-height: 140px;
            width: auto;
        }

        .email-heading {
            margin: 28px 0 0;
            color: #21150f;
            font-size: 32px;
            line-height: 1.25;
        }

        .email-content {
            padding: 12px 54px 42px;
            font-size: 16px;
        }

        .email-content h2,
        .email-content h3 {
            color: #5f50a6;
            line-height: 1.3;
        }

        .email-content h2 {
            margin: 22px 0 10px;
            font-size: 24px;
        }

        .email-content h3 {
            margin: 20px 0 8px;
            font-size: 19px;
        }

        .email-content p {
            margin: 0 0 16px;
        }

        .email-content ul,
        .email-content ol {
            margin: 8px 0 20px;
            padding-left: 24px;
        }

        .email-content li {
            margin-bottom: 6px;
        }

        .email-content a {
            color: #5f50a6;
        }

        .button-row {
            margin: 26px 0 12px;
        }

        .button {
            display: inline-block;
            margin: 0 8px 10px 0;
            padding: 12px 22px;
            border: 2px solid #5f50a6;
            border-radius: 7px;
            background: #5f50a6;
            color: #ffffff !important;
            font-weight: bold;
            line-height: 1.2;
            text-decoration: none;
        }

        .button-secondary {
            background: #ffffff;
            color: #5f50a6 !important;
        }

        .email-footer {
            padding: 28px 54px 32px;
            border-top: 1px solid #e4ded9;
            background: #fdfcfc;
            color: #716963;
            font-size: 13px;
            text-align: center;
        }

        .email-footer p {
            margin: 0 0 10px;
        }

        .footer-links {
            line-height: 1.8;
        }

        .footer-links a {
            display: inline-block;
            margin: 3px 8px;
            color: #5f50a6;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
        }

        .footer-link-separator {
            display: inline-block;
            color: #b8aea7;
            font-size: 9px;
            vertical-align: middle;
        }

        @media only screen and (max-width: 620px) {
            .email-shell-pad {
                padding: 14px !important;
            }

            .email-header {
                padding: 30px 24px 16px;
            }

            .email-content {
                padding: 8px 24px 30px;
            }

            .email-footer {
                padding: 24px;
            }

            .email-heading {
                font-size: 26px;
            }

            .button {
                box-sizing: border-box;
                display: block;
                margin-right: 0;
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <table class="email-shell" role="presentation" width="100%">
        <tr>
            <td class="email-shell-pad" align="center">
                <table class="email-card" role="presentation" width="680">
                    <tr>
                        <td class="email-header">
                            <?php if ($logo_url !== ''): ?>
                                <a href="<?php echo base_url(); ?>" aria-label="<?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?> home">
                                    <img class="brand-logo" src="<?php echo htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8'); ?>" height="140" alt="<?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?> logo">
                                </a>
                            <?php endif; ?>
                            <h1 class="email-heading"><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content">
                            <?php echo $body; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-footer">
                            <?php if ($copyright_text !== '' || $license_number !== ''): ?>
                                <p>
                                    <?php echo htmlspecialchars($copyright_text, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($copyright_text !== '' && $license_number !== ''): ?>
                                        &nbsp;-&nbsp;
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($license_number, ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($footer)): ?>
                                <div class="footer-links"><?php echo $footer; ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
