<?php

# CONFIGURATION
# Du kannst hier die benötigten Informationen eintragen.

$CONFIG = [
    "praxis" => "Deine Praxis",
    "website" => "https://www.deine-website.de",
    "impressum" => "https://www.deine-website.de/impressum",
    "datenschutz" => "https://www.deine-website.de/datenschutz",
    "mindestalter" => 60
];


# CODE
# Bitte modifiziere nicht was hier steht (es sei denn du weißt, was du tust).
require "vendor/autoload.php";


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
            return false;
        }
        $values[$key] = htmlspecialchars($_POST[$value]);
    }

    if (!filter_var($values["email"], FILTER_VALIDATE_EMAIL))
        return false;

    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumberUtil->parse($values["phone"], "DE");
    } catch (\libphonenumber\NumberParseException $e) {
        return false;
    }


    return $values;
}

function send_mail($data)
{
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


session_start();


if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $form_ids = generate_ids();

    $form_visible = true;
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (array_key_exists("FORM_IDS", $_SESSION)) {
        $form_ids = $_SESSION["FORM_IDS"];
        unset($_SESSION["FORM_IDS"]);

        $data = validate_request($form_ids);
        if ($data === false) {
            $error_message = $error_messages["error_validating"];
            $form_ids = generate_ids();
            $form_visible = true;
        } else {
            if (calculate_age($data["birth-day"], $data["birth-month"], $data["birth-year"]) >= $CONFIG["mindestalter"]) {
                $successfully_send = send_mail($data);
                if ($success_message) {
                } else {
                    $error_message = $error_messages["error_sending"];
                    $form_ids = generate_ids();
                    $form_visible = true;
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
        if (!$form_visible) {
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
            <form class="row g-3" method="POST">
                <div class="col-md-4">
                    <label for="<?= $form_ids["first-name"]; ?>" class="form-label">Vorname</label>
                    <input type="text" class="form-control" id="<?= $form_ids["first-name"]; ?>" name="<?= $form_ids["first-name"]; ?>" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label for="<?= $form_ids["last-name"]; ?>" class="form-label">Nachname</label>
                    <input type="text" class="form-control" id="<?= $form_ids["last-name"]; ?>" name="<?= $form_ids["last-name"]; ?>" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label for="<?= $form_ids["birth-day"]; ?>" class="form-label">Geburtstag</label>
                    <div class="d-flex justify-content-between">
                        <input type="number" min="1" max="31" class="form-control" id="<?= $form_ids["birth-day"]; ?>" name="<?= $form_ids["birth-day"]; ?>" placeholder="Tag" autocomplete="off" required>
                        <input type="number" min="1" max="12" class="form-control ms-1" id="<?= $form_ids["birth-month"]; ?>" name="<?= $form_ids["birth-month"]; ?>" placeholder="Monat" autocomplete="off" required>
                        <input type="number" min="1900" max="2021" class="form-control ms-1" id="<?= $form_ids["birth-year"]; ?>" name="<?= $form_ids["birth-year"]; ?>" placeholder="Jahr" autocomplete="off" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="<?= $form_ids["email"]; ?>" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="<?= $form_ids["email"]; ?>" name="<?= $form_ids["email"]; ?>" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label for="<?= $form_ids["phone"]; ?>" class="form-label">Mobilnummer (WhatsApp)</label>
                    <input type="tel" class="form-control" id="<?= $form_ids["phone"]; ?>" name="<?= $form_ids["phone"]; ?>" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label for="<?= $form_ids["vaccine-type"]; ?>" class="form-label">Impfstoff</label>
                    <select id="<?= $form_ids["vaccine-type"]; ?>" name="<?= $form_ids["vaccine-type"]; ?>" class="form-select" autocomplete="off" required>
                        <option selected value="irrelevant">Ist mir egal</option>
                        <option value="biontech">Biontech</option>
                        <option value="astrazeneca">AstraZeneca</option>
                    </select>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Impftermin beantragen</button>
                </div>
            </form>
        <?php } ?>
        <hr class="mt-5" />
        <p class="text-center mb-0">&copy; <?= date("Y") . " " . $CONFIG["praxis"]; ?></p>
        <div class="d-flex justify-content-evenly mb-5">
            <a href="<?= $CONFIG["website"] ?>">Website</a>
            <a href="<?= $CONFIG["impressum"]; ?>">Impressum</a>
            <a href="<?= $CONFIG["datenschutz"]; ?>">Datenschutz</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
</body>

</html>