<?php
// prod/offers/offer_delete.php

// 1. Load the environment (Composer + Config + DB Connection)
require_once __DIR__ . '/../util/config.php';

// Assuming $pdo is created in util/db_connect.php (included via config)
$offerRepo = new OfferRepository($pdo);

// 2. Logic: Attempt deletion if ID is provided
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // In the future, you can add a check here: 
    // if ($user->role === 'admin' || $user->id === $offer->owner_id)
    $offerRepo->delete($id);
}

// 3. Response: Redirect back to the search page or home
header("Location: " . (defined('SITE_URL') ? SITE_URL : '/') . "/offers/offer_search.php");
exit();