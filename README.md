Embedded (Nested) Models Extension for Yii 2
============================================

This extension provides support for embedded (nested) models usage in Yii2.
In particular it allows working with sub-documents in MongoDB and ElasticSearch.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/embedded/v/stable.png)](https://packagist.org/packages/yii2tech/embedded)
[![Total Downloads](https://poser.pugx.org/yii2tech/embedded/downloads.png)](https://packagist.org/packages/yii2tech/embedded)
[![Build Status](https://travis-ci.org/yii2tech/embedded.svg?branch=master)](https://travis-ci.org/yii2tech/embedded)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/embedded
```

or add

```json
"yii2tech/embedded": "*"
```

to the require section of your composer.json.


Usage
-----

This extension grants the ability to work with complex model attributes, represented as arrays, as nested models,
represented as objects.
To use this feature the target class should implement [[\yii2tech\embedded\ContainerInterface]] interface.
This can be easily achieved using [[\yii2tech\embedded\ContainerTrait]].

For each embedded entity a mapping declaration should be provided.
In order to do so you need to declare method, which name is prefixed with 'embedded', which
should return the [[Mapping]] instance. You may use [[hasEmbedded()]] and [[hasEmbeddedList()]] for this.

Per each of source field or property a new virtual property will declared, which name will be composed
by removing 'embedded' prefix from the declaration method name.

> Note: watch for the naming collisions: if you have a source property named 'profile' the mapping declaration
  for it should have different name, like 'profileModel'.


Example:

```php
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

class User extends Model implements ContainerInterface
{
    use ContainerTrait;

    public $profileData = [];
    public $commentsData = [];

    public function embedProfile()
    {
        return $this->mapEmbedded('profileData', Profile::className());
    }

    public function embedComments()
    {
        return $this->mapEmbeddedList('commentsData', Comment::className());
    }
}

$user = new User();
$user->profile->firstName = 'John';
$user->profile->lastName = 'Doe';

$comment = new Comment();
$user->comments[] = $comment;
```

Each embedded mapping may have additional options specified. Please refer to [[\yii2tech\embedded\Mapping]] for more details.


## Validating embedded models <span id="validating-embedded-models"></span>

Each embedded model should declare its own validation rules and, in general, should be validated separately.
However, you may simplify complex model validation using [[\yii2tech\embedded\Validator]].
For example:

```php
class User extends Model implements ContainerInterface
{
    use ContainerTrait;

    public $contactData;

    public function embedContact()
    {
        return $this->mapEmbedded('contactData', Contact::className());
    }

    public function rules()
    {
        return [
            ['contact', 'yii2tech\embedded\Validator'],
        ]
    }
}

class Contact extends Model
{
    public $email;

    public function rules()
    {
        return [
            ['email', 'required'],
            ['email', 'email'],
        ]
    }
}

$user = new User();
if ($user->populate(Yii::$app->request->post()) && $user->contact->populate(Yii::$app->request->post())) {
    if ($user->validate()) { // automatically validates 'contact' as well
        // ...
    }
}
```
