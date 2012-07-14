<?php
require('cheezburger.php');
$key 	= 'b73fdea753085480a5bf159973f6ae7b';
$secret = '7bb0d65b80d5db605b0f092936eb7a07';
$uri 	= 'http://localhost:3000/oauth/callback';

// Pre-generated token
$token 	= 'e35e35eeab90803ccb262e9e2166cc82';

$Cheezburger = new CheezburgerClient($key, $secret, $uri);
$Cheezburger->setToken($token);

// API OAuth
$authUrl = $Cheezburger->getAuthUrl();
echo "Go here: {$authUrl} \n";

// API Methods
$assetTypes = $Cheezburger->assetTypes();
if(!$assetTypes) { echo "Error occured: {$Cheezburger->getError()}"; }
else { print_r($assetTypes); }

$ohai = $Cheezburger->ohai("Now here's a message for ya");
if(!$ohai) { echo "Error occured: {$Cheezburger->getError()}"; }
else { print_r($ohai); }

$me = $Cheezburger->me();
if(!$me) { echo "Error occured: {$Cheezburger->getError()}"; }
else { print_r($me); }

$user = $Cheezburger->user('BaconSeason');
if(!$user) { echo "Error occured: {$Cheezburger->getError()}"; }
else { print_r($user); }

// Clear line in terminal
echo "\n";
echo "\n";