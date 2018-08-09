#!/bin/bash
for i in *Test.php; do
    echo 'Running Test File: '$i
    phpunit --verbose --configuration phpunit.xml echo $i
done