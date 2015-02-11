Barcode Generator
===================================


Install via Composer
-----------------------

```
    "repositories": [
            {
                "type": "vcs",
                "url": "git@github.com:makari4/barcode.git"
            }
        ],
    "require": {
        "makari4/barcode":"1.0.0"
    },

```

Usage
----------------------

```

use makari4\barcode\Barcode39;

$barcode = new Barcode39();
$barcode->generate('test barcode');


```