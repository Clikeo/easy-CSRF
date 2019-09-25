Easy CSRF - Cross Site Request Forgery Protection
========================================================

This library is a simple signature generator to protect form submissions from cross site request forgery, using a signed token. It does not require server-side storage of valid tokens and is thereby stateless.

Install
------------

composer require itrack/csrf


Simple usage
------------


```php
$secret = '948thksehbf23fnoug2p4g2o...'; // well chosen secret
$signer = new \Itrack\CSRF\SignatureGenerator($secret);

if ($_POST) {
    if (!$signer->validateSignature($_POST['_token'])) {
        header('HTTP/1.0 400 Bad Request');
        exit;
    }
}
```

```html
<form action="" method="post">
    <?php printf('<input type="hidden" name="_token" value="%s">', $signer->getSignature()); ?>
    ...
    <input type="submit" value="Submit">
</form>
```

The `SignatureGenerator` needs the be instantiated with the same secret every time. To generate a signed token, simply call `SignatureGenerator::getSignature` and embed the value into a hidden form field. Upon form submission, validate this token using `SignatureGenerator::validateSignature`.

Time limited validity
---------------------

The signature includes a timestamp of when it was generated. This can be used to expire it after some time. The timestamp is part of the signature generation process and cannot be altered. By default the signature expires after a few hours (see `SignatureGenerator::$validityWindow` for default value). You can set your own validity window using `SignatureGenerator::setValidityWindow`:

```php
$signer->setValidityWindow(time() - 3600);
$signer->setValidityWindow('-1 hour');
$signer->setValidityWindow(new DateTime('-1 hour'));
```

The method accepts an integer UNIX timestamp, a string which will be evaluated by `strtotime` or an instance of `DateTime`. Any signature older than the set timestamp will be regarded as expired. The default timeout should present a reasonable value which makes sure signatures do expire eventually, without frustrating slow users. Adjust it to make it tighter or more relaxed based on your needs.

Adding data
-----------

The signature can additionally be used to protect against form field injection and/or can be tied to a specific user. Data can be added to the signature generation process using `SignatureGenerator::addValue` and `SignatureGenerator::addKeyValue`:

```php
$signer->addValue('foo');
$signer->addKeyValue('bar', 'baz');
```

The signature will only be valid if the same data was added when the token was generated and when it is being validated. To protect against form field injection you should add the names of all `<input>` elements which you expect to receive in the submitted form using `SignatureGenerator::addValue`. Any additional data you want to tie to the signature, like the user id, should be added using `SignatureGenerator::addKeyValue`.

For example, when generating the token:

```php
$signer = new \Itrack\CSRF\SignatureGenerator($secret);

// including user id in signature
// 'userid' is an arbitrarily chosen key name
$signer->addKeyValue('userid', $_SESSION['User']['id']);

// including names of valid form fields in signature
$signer->addValue('_token');
$signer->addValue('firstname');
$signer->addValue('lastname');
```

```html
<form action="" method="post">
    <?php printf('<input type="hidden" name="_token" value="%s">', $signer->getSignature()); ?>
    <input type="text" name="firstname">
    <input type="text" name="lastname">
    <input type="submit" value="Submit">
</form>
```

When validating the token, use the submitted form fields as part of the validation:

```php
$signer = new \Itrack\CSRF\SignatureGenerator($secret);

// including user id in signature validation
$signer->addKeyValue('userid', $_SESSION['User']['id']);

// including submitted form fields in signature validation
foreach (array_keys($_POST) as $key) {
    $signer->addValue($key);
}

if (!$signer->validateSignature($_POST['_token'])) {
    // error
}
```

This way, if any fields which were not part of the original signature are submitted with the form, it will not validate. Take care if you're dynamically adding form fields using Javascript.

### Note

The drawback of adding form fields is that the same form fields need to be added when generating the signature and when validating it. This requires to keep the list of expected and actual form fields in sync, which can quickly lead to code duplication if not handled properly. For best results I'd recommend using this library as part of a larger form generating function/class/library which handles this.

Signature format
----------------

The signature is encoded in base64, format by default is:

    timestamp + ":" + token + ":" + signed token

where

    timestamp    = unsigned integer
    token        = base64 encoded random value
    signed token = base64 encoded hash
    
    hash         = HMAC_SHA512(timestamp + token + data, secret)
    data         = all added values

The `data` is sorted, so the order in which the values are added does not matter. The above description omits technical details on which exact format the data is put in for hashing, please consult the source code.

Crypto provider
---------------

An alternative `CryptoProvider`, which provides a source of randomness and the hashing algorithm, can be passed upon instantiating `SignatureGenerator` as the second argument to the constructor. Consult `ICryptoProvider.php` and `CryptoProvider.php`.

Information
-----------

Based on https://github.com/deceze/Kunststube-CSRFP package

