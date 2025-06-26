#!/bin/bash
`which phpstan.phar` --version
if [ 0 -eq $# ]
then

	php8.4 `which phpstan.phar` analyse --configuration phpstan.neon  ../
else

	for var in "$@"
	do
		if [ "$var" == "--clear" ]
		then

			php8.4 `which phpstan.phar` clear-result-cache --configuration phpstan.neon 
			php8.4 `which phpstan.phar` analyse --configuration phpstan.neon  ../
			exit
		fi

	done

	php8.4 `which phpstan.phar` analyse --configuration phpstan.neon $@
fi

