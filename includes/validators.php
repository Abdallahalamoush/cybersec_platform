<?php

function validate_password_policy(string $password): array {
    $errors = [];

    if (strlen($password) < 10) {
        $errors[] = "Le mot de passe doit contenir au moins 10 caractères.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Ajoute au moins une lettre majuscule.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Ajoute au moins une lettre minuscule.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Ajoute au moins un chiffre.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Ajoute au moins un caractère spécial (!@#$...).";
    }

    return $errors;
}
