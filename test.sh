#!/bin/bash
version=$(grep -e 'Version:' tailwind10f.php | sed 's/.*Version:\s\+\([\d\.]*\)/\1/')
echo "[$version]"
