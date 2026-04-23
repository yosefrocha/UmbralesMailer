<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "INI OK<br>";

require __DIR__ . '/config/app.php';
echo "CONFIG OK<br>";

require __DIR__ . '/core/Session.php';
echo "SESSION OK<br>";

require __DIR__ . '/core/View.php';
echo "VIEW OK<br>";

require __DIR__ . '/core/Csrf.php';
echo "CSRF OK<br>";

require __DIR__ . '/core/Model.php';
echo "MODEL OK<br>";

require __DIR__ . '/core/Controller.php';
echo "CONTROLLER OK<br>";

require __DIR__ . '/app/models/Recipient.php';
echo "RECIPIENT OK<br>";

require __DIR__ . '/app/models/SendSession.php';
echo "SENDSESSION OK<br>";

require __DIR__ . '/app/models/CampaignRecipient.php';
echo "CAMPAIGNRECIPIENT OK<br>";

require __DIR__ . '/core/App.php';
echo "APP OK<br>";

require __DIR__ . '/app/controllers/AuthController.php';
echo "AUTHCONTROLLER OK<br>";