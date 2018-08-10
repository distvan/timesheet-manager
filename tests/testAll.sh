#!/bin/bash
for i in *Test.php; do
    echo 'Running Test File: '$i
    phpunit --configuration phpunit.xml echo $i
done