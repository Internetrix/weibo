# weibo

## Introduction

Weibo module enables CMS log in through weibo account. Since only authorized weibo apps can gain access to user emails, 
it also provides a verification process and a form to save user email.

## Maintainer Contact

 * Yuchen Liu [Internetrix]

## Requirements

 * SilverStripe 3.1

## Features

*  This modules uses Weibo OAuth2 Api lib 1.0
*  It has ability to get user basic weibo account info like name, location, profile image, interests, etc.
*  It provides login by using weibo account and create a Member instance on database
*  Since only authorized weibo apps can gain access to user emails,  it checks if user email exists and redirect users without email info to a form page to gain user emails.
*  This module enables login using weibo account from both English and Chinese websites.

## Installation

Installation can be done either by composer or by manually downloading a release.

### Via composer

`composer require "silverstripe/userforms:*"`

### Manually

 1.  Download the module from [the releases page](https://github.com/Internetrix/weibo).
 2.  Extract the file (if you are on windows try 7-zip for extracting tar.gz files
 3.  Make sure the folder after being extracted is named 'weibo' 
 4.  Place this directory in your sites root directory. This is the one with framework and cms in it.

### Configuration

After installation, make sure you rebuild your database through `dev/build`.

Copy weibo/_config/weibo.yml to mysite/_config/weibo.yml and put in your weibo app details.