<?php

/* 
* KONFIGURATION *
Sie können hier die benötigten Informationen eintragen.
! BITTE BEACHTE: Sie müssen für die E-Mail Einstellungen die SMTP Daten Ihres Mail Servers angeben.
*/
$CONFIG = [
    "praxis" =>             "Deine Praxis", // Dieser Name wird überall auf der Website angezeigt.
    "website" =>            "https://www.deine-website.de", // Dieser Link wird unter anderem dafür benutzt, um über den Zurück Knopf auf ihre Website zu gelangen.
    "impressum" =>          "https://www.deine-website.de/impressum",
    "datenschutz" =>        "https://www.deine-website.de/datenschutz",
    "mindestalter" =>       0, // Wenn das Mindestalter hochgestellt wird, dann können sich nur Benutzter eines bestimmten Alters einen Termin beantragen.
    "wartezeit_info" =>     "Bitte bedenken Sie, dass bei spezifischer Auswahl des Impfstoffes eine längere Wartezeit anfallen könnte.", // Für keine einfach auf "" setzten.
    "mobilnummer_suffix" => "(WhatsApp)", // Für keinen einfach auf "" setzten.
    "email_sender" =>       "mail@dein-server.de", // Der E-Mail Sender muss wenn möglich mit dem E-Mail Benutzernamen übereinstimmen.
    "email_empfaenger" => [
        "mail@dein-server.de", // Sie können hier so viele E-Mail Adressen angeben wie sie wollen.
        "mail2@dein-server.de"
    ],
    "email_einstellungen" => [ // Für diese einstellungen fragen sie wennn möglich ihren Web-Admin.
        "host" =>       "smtp.dein-server.de",
        "username" =>   "mail@dein-server.de",
        "password" =>   "super_geheimes_passowort",
        "encryption" => "tls", // Optional wäre auch "ssl" möglich, welche mehr sicherheit bietet. Bitte überprüfen Sie bitte vorerst, ob ihr Mail-Server dies unterstützt.
        "port" =>       587
    ],
];


/*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*
*/

# CODE
# Bitte modifiziere nicht was hier steht (es sei denn du weißt, was du tust).
require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function rand_string($length)
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $size = strlen($chars);
    $result = "";
    for ($i = 0; $i < $length; $i++) {
        $str = $chars[rand(0, $size - 1)];
        $result = $result . $str;
    }
    return $result;
}

function calculate_age($birth_day, $birth_month, $birth_year)
{
    return (date("md", date("U", mktime(0, 0, 0, $birth_month, $birth_day, $birth_year))) > date("md")
        ? ((date("Y") - $birth_year) - 1)
        : (date("Y") - $birth_year));
}

function generate_ids()
{
    $form_ids = array();
    $form_names = ["first-name", "last-name", "birth-day", "birth-month", "birth-year", "vaccine-type", "email", "phone"];
    foreach ($form_names as $name) {
        $res = null;
        while (true) {
            $res = rand_string(10);
            if (!in_array($res, $form_ids))
                break;
        }
        $form_ids[$name] = $res;
    }
    $_SESSION["FORM_IDS"] = $form_ids;

    return $form_ids;
}

function validate_request($form_ids)
{
    $values = array();
    foreach ($form_ids as $key => $value) {
        if (!isset($_POST[$value])) {
            //echo $key . ":" . $value;
            return [false, $values];
        }
        $values[$key] = htmlspecialchars($_POST[$value]);
    }

    if (!filter_var($values["email"], FILTER_VALIDATE_EMAIL))
        return [false, $values];

    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumberUtil->parse($values["phone"], "DE");
    } catch (\libphonenumber\NumberParseException $e) {
        return [false, $values];
    }

    if (!in_array($values["vaccine-type"], ["irrelevant", "biontech", "astrazeneca"]))
        return [false, $values];

    if (!is_numeric($values["birth-day"]) || !is_numeric($values["birth-month"]) || !is_numeric($values["birth-year"]))
        return [false, $values];

    return [true, $values];
}

function send_mail($data, $CONFIG)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host =       $CONFIG["email_einstellungen"]["host"];
        $mail->SMTPAuth =   true;
        $mail->Username =   $CONFIG["email_einstellungen"]["username"];
        $mail->Password =   $CONFIG["email_einstellungen"]["password"];
        $mail->SMTPSecure = $CONFIG["email_einstellungen"]["encryption"];
        $mail->Port =       $CONFIG["email_einstellungen"]["port"];

        $mail->setFrom($CONFIG["email_sender"], $CONFIG["praxis"]);
        foreach ($CONFIG["email_empfaenger"] as $address) {
            $mail->addAddress($address);
        }
        $mail->addReplyTo($data["email"], $data["first-name"] . " " . $data["last-name"]);

        $mail->isHTML(true);
        $mail->Subject = "Impftermin beantragt von " . $data["first-name"] . " " . $data["last-name"];
        $age = calculate_age($data["birth-day"], $data["birth-month"], $data["birth-year"]);
        $vaccine_types = ["irrelevant" => "Beliebig", "biontech" => "Biontech", "astrazeneca" => "AstraZeneca"];
        $vaccine_color = $data["vaccine-type"] === "irrelevant" ? "" : "";
        $mail->Body = "<!DOCTYPE html>
<html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\" />
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
        <style>
            ul li {
                list-style-type: none;
            }
        </style>
    </head>
    <body>
        <h3>Es wurde ein Impftermin beantragt.</h3>
        <ul>
            <li><b>Name:</b> {$data["first-name"]} {$data["last-name"]}</li>
            <li><b>Geburtstag:</b> {$data["birth-day"]}.{$data["birth-month"]}.{$data["birth-year"]} ({$age} Jahre)</li>
            <li><b>E-Mail:</b> {$data["email"]}</li>
            <li><b>Mobilnummer:</b> {$data["phone"]}</li>
            <li><b>Impfstoff:</b> {$vaccine_types[$data["vaccine-type"]]}</li>
        </ul>
    </body>
</html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

$error_messages = [
    "error_sending" => "Bei der Sendung des Formulars ist ein Fehler aufgetreten.",
    "error_validating" => "Deine Angaben sind nicht korrekt. Bitte überprüfe diese und versuche es erneut.",
    "error_to_young" => "Du kannst leider erst einen Impftermin ab einem Alter von " . $CONFIG["mindestalter"] . " Jahren vereinbaren."
];

$form_ids = array();
$form_visible = false;
$error_message = null;
$success_message = null;
$data_ = null;


session_start();


if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $form_ids = generate_ids();

    $form_visible = true;
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (array_key_exists("FORM_IDS", $_SESSION)) {
        $form_ids = $_SESSION["FORM_IDS"];
        unset($_SESSION["FORM_IDS"]);

        $data = validate_request($form_ids);
        if ($data[0] === false) {
            $error_message = $error_messages["error_validating"];
            $form_ids = generate_ids();
            $form_visible = true;
            $data_ = $data[1];
        } else {
            $data = $data[1];
            if (calculate_age($data["birth-day"], $data["birth-month"], $data["birth-year"]) >= $CONFIG["mindestalter"]) {
                $successfully_send = send_mail($data, $CONFIG);
                if ($successfully_send == true) {
                    $form_visible = false;
                    $success_message = "Ihr Antrag für einen Impftermin wurde erfolgreich abgesendet.";
                } else {
                    $error_message = $error_messages["error_sending"] . " " . $successfully_send;
                    $form_ids = generate_ids();
                    $form_visible = true;
                    $data_ = $data;
                }
            } else {
                $error_message = $error_messages["error_to_young"];
                $form_visible = false;
            }
        }
    } else {
        $form_visible = false;
        $error_message = $error_messages["error_sending"];
    }
} else {
    $form_visible = false;
    $error_message = $error_messages["error_sending"];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $CONFIG["praxis"]; ?> - Impftermin beantragen</title>
    <link rel="shortcut icon" href="https://imgur.com/7OMk8ys.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
    <style>
        @media only screen and (max-width: 600px) {
            .w-50 {
                width: calc(100% - 1rem) !important;
            }
        }
    </style>
</head>

<body>

    <div class="w-50 mx-auto">
        <?php
        $back_button_link = "javascript:window.history.back();";
        if ($success_message != "") {
            $back_button_link = $CONFIG["website"];
        } elseif (!$form_visible || $_SERVER["REQUEST_METHOD"] != "GET") {
            $back_button_link = $_SERVER["REQUEST_URI"];
        }
        ?>
        <a href="<?= $back_button_link; ?>" class="btn btn-light mt-3">
            &larr; Zurück </a>
        <div class="mt-5">
            <p class="mb-0 fs-5"><?= $CONFIG["praxis"]; ?></p>
            <h1 class="display-5 mt-0">Impftermin beantragen</h1>
            <hr />
        </div>
        <?php if ($error_message != null) { ?>
            <div class="p-3 mb-3 text-white bg-danger rounded">
                <?= $error_message ?>
            </div>
        <?php } ?>
        <?php if ($success_message != null) { ?>
            <div class="p-3 mb-3 text-white bg-success rounded">
                <?= $success_message ?>
            </div>
        <?php } ?>
        <?php if ($form_visible) { ?>
            <form method="POST" id="form">
                <fieldset class="w-100 row g-3" id="form">
                    <div class="col-md-4">
                        <label for="<?= $form_ids["first-name"]; ?>" class="form-label">Vorname</label>
                        <input type="text" class="form-control" id="<?= $form_ids["first-name"]; ?>" value="<?= isset($data_["first-name"]) ? $data_["first-name"] : "" ?>" name="<?= $form_ids["first-name"]; ?>" autocomplete="off" required>
                    </div>
                    <div class="col-md-4">
                        <label for="<?= $form_ids["last-name"]; ?>" class="form-label">Nachname</label>
                        <input type="text" class="form-control" id="<?= $form_ids["last-name"]; ?>" value="<?= isset($data_["last-name"]) ? $data_["last-name"] : "" ?>" name="<?= $form_ids["last-name"]; ?>" autocomplete="off" required>
                    </div>
                    <div class="col-md-4">
                        <label for="<?= $form_ids["birth-day"]; ?>" class="form-label">Geburtstag</label>
                        <div class="d-flex justify-content-between">
                            <input type="number" min="1" max="31" class="form-control" id="<?= $form_ids["birth-day"]; ?>" value="<?= isset($data_["birth-day"]) ? $data_["birth-day"] : "" ?>" name="<?= $form_ids["birth-day"]; ?>" placeholder="Tag" autocomplete="off" required>
                            <input type="number" min="1" max="12" class="form-control ms-1" id="<?= $form_ids["birth-month"]; ?>" value="<?= isset($data_["birth-month"]) ? $data_["birth-month"] : "" ?>" name="<?= $form_ids["birth-month"]; ?>" placeholder="Monat" autocomplete="off" required>
                            <input type="number" min="1900" max="2021" class="form-control ms-1" id="<?= $form_ids["birth-year"]; ?>" value="<?= isset($data_["birth-year"]) ? $data_["birth-year"] : "" ?>" name="<?= $form_ids["birth-year"]; ?>" placeholder="Jahr" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="<?= $form_ids["email"]; ?>" class="form-label">E-Mail</label>
                        <input type="email" class="form-control" id="<?= $form_ids["email"]; ?>" value="<?= isset($data_["email"]) ? $data_["email"] : "" ?>" name="<?= $form_ids["email"]; ?>" autocomplete="off" required>
                    </div>
                    <div class="col-md-4">
                        <label for="<?= $form_ids["phone"]; ?>" class="form-label">Mobilnummer <?= $CONFIG["mobilnummer_suffix"] ?></label>
                        <input type="tel" class="form-control" id="<?= $form_ids["phone"]; ?>" value="<?= isset($data_["phone"]) ? $data_["phone"] : "" ?>" name="<?= $form_ids["phone"]; ?>" autocomplete="off" required>
                    </div>
                    <div class="col-md-4">
                        <label for="<?= $form_ids["vaccine-type"]; ?>" class="form-label">Impfstoff</label>
                        <select id="<?= $form_ids["vaccine-type"]; ?>" name="<?= $form_ids["vaccine-type"]; ?>" class="form-select" autocomplete="off" required>
                            <option selected value="irrelevant">Beliebig</option>
                            <option value="biontech">Biontech</option>
                            <option value="astrazeneca">AstraZeneca</option>
                        </select>
                    </div>

                    <?php if ($CONFIG["wartezeit_info"] !== "") { ?>
                        <div class="col-12 rounded bg-warning p-3 box-sizing d-none" id="info-box">
                            <?= $CONFIG["wartezeit_info"]; ?>
                        </div>
                    <?php } ?>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary d-flex align-items-center">
                            <div class="spinner-border text-light me-3 d-none" role="status" id="submitting">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span>
                                Impftermin beantragen
                            </span>
                        </button>
                    </div>
                </fieldset>
            </form>
        <?php } ?>
        <hr class="mt-5" />
        <div class="d-flex justify-content-between mb-5">
            <p class="text-center mb-0">&copy; <?= date("Y") . " " . $CONFIG["praxis"]; ?></p>
            <div class="d-flex">
                <a href="<?= $CONFIG["website"] ?>">Website</a>
                <a href="<?= $CONFIG["impressum"]; ?>" class="ms-3">Impressum</a>
                <a href="<?= $CONFIG["datenschutz"]; ?>" class="ms-3">Datenschutz</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>
        <?php if ($CONFIG["wartezeit_info"] !== "" && $form_visible) { ?>
            document.querySelector("select#<?= $form_ids["vaccine-type"] ?>").onchange = function() {
                console.log(this.value);
                if (this.value !== "irrelevant") {
                    document.querySelector("div#info-box").classList.remove("d-none");
                } else {
                    document.querySelector("div#info-box").classList.add("d-none");
                }
            }
        <?php } ?>

        <?php if ($form_visible) { ?>
            document.querySelector("form#form").onsubmit = () => {
                document.querySelector("fieldset#form").querySelectorAll("input, select, button").forEach((el) => {
                    el.readonly = true;
                    el.classList.add("disabled");
                })
                document.querySelector("div#submitting").classList.remove("d-none");
            }
        <?php } ?>
    </script>
</body>

</html>