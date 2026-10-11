<?php
session_start();

// Returns true if no user is logged in.
function isGuest()
{
    return !isset($_SESSION['user']);
}

// Returns true only if the current user's role is 'customer'.
function isCustomer()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
}

// Returns true only if the current user's role is 'admin'.
function isAdmin()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

// Returns true only if the current user's role is 'pharmacist'.
function isPharmacist()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'pharmacist';
}

// Returns true only if the current user's role is 'storekeeper'.
function isStorekeeper()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'storekeeper';
}
