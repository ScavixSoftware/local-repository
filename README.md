Local Composer Repository
=========================
This plugin allows you to specify a local folder containing your composer packages.
They will be appended as if you would add them to the `repositories` in your `composer.json`, but they will
only be loaded if the path really exist.

Configuration
=============
```
{
    "require": {
        "scavix/local-repository": "^1.0.0"
    },
    "extra": {
        "local-repositories": [
			"./my-local-repo"
		]
    }
}
```