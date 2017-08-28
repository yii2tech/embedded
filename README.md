<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">Embedded (Nested) Models Extension for Yii 2</h1>
    <br>
</p>

This extension provides support for embedded (nested) models usage in Yii2.
In particular it allows working with sub-documents in [MongoDB](https://github.com/yiisoft/yii2-mongodb) and [ElasticSearch](https://github.com/yiisoft/yii2-elasticsearch).

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

Per each of source field or property a new virtual property will be declared, which name will be composed
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


## Processing embedded objects <span id="processing-embedded-objects"></span>

Embedded feature is similar to regular ActiveRecord relation feature. Their declaration and processing are similar
and have similar specifics and limitations.
All embedded objects are lazy loaded. This means they will not be created until first demand. This saves memory
but may produce unexpected results at some point.
By default, once embedded object is instantiated its source attribute will be unset in order to save memory usage.
You can control this behavior via [[\yii2tech\embedded\Mapping::$unsetSource]].

Embedded objects allow simplification of nested data processing, but usually they know nothing about their source
data meaning and global processing. For example: nested object is not aware if its source data comes from database
and it does not know how this data should be saved. Such functionality usually is handled by container object.
Thus at some point you will need to convert data from embedded objects back to its raw format, which allows its
native processing like saving. This can be done using method `refreshFromEmbedded()`:

```php
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

class User extends Model implements ContainerInterface
{
    use ContainerTrait;

    public $profileData = [
        'firstName' => 'Unknown',
        'lastName' => 'Unknown',
    ];

    public function embedProfile()
    {
        return $this->mapEmbedded('profileData', Profile::className());
    }
}

$user = new User();
var_dump($user->profileData); // outputs array: ['firstName' => 'Unknown', 'lastName' => 'Unknown']

$user->profile->firstName = 'John';
$user->profile->lastName = 'Doe';

var_dump($user->profileData); // outputs empty array

$user->refreshFromEmbedded();
var_dump($user->profileData); // outputs array: ['firstName' => 'John', 'lastName' => 'Doe']
```

While embedding list of objects (using [[\yii2tech\embedded\ContainerTrait::mapEmbeddedList()]]) the produced
virtual field will be not an array, but an object, which satisfies [[\ArrayAccess]] interface. Thus all manipulations
with such property (even if it may look like using array) will affect container object.
For example:

```php
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

class User extends Model implements ContainerInterface
{
    use ContainerTrait;

    public $commentsData = [];

    public function embedComments()
    {
        return $this->mapEmbeddedList('commentsData', Comment::className());
    }
}

$user = new User();
// ...

$comments = $user->comments; // not a copy of array - copy of object reference!
foreach ($comments as $key => $comment) {
    if (...) {
        unset($comments[$key]); // unsets `$user->comments[$key]`!
    }
}

$comments = clone $user->comments; // creates a copy of list, but not a copy of contained objects!
$comments[0]->title = 'new value'; // actually sets `$user->comments[0]->title`!
```


## Validating embedded models <span id="validating-embedded-models"></span>

Each embedded model should declare its own validation rules and, in general, should be validated separately.
However, you may simplify complex model validation using [[\yii2tech\embedded\Validator]].
For example:

```php
use yii\base\Model;
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

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
            // ...
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
if ($user->load(Yii::$app->request->post()) && $user->contact->load(Yii::$app->request->post())) {
    if ($user->validate()) { // automatically validates 'contact' as well
        // ...
    }
}
```

> Note: pay attention that [[\yii2tech\embedded\Validator]] must be set for the embedded model name - not for its
  source attribute. Do not mix them up!

You can enable [[\yii2tech\embedded\Validator::$initializedOnly]], allowing to skip validation for the embedded model, if
it has not been initialized, e.g. requested at least once. This will save the performance in case source model can be used
in different scenarios, some of which may not require embedded model manipulations. However, in this case embedded source
attribute value will not be validated. You should ensure it validated in other way or it is 'unsafe' for population via
[[\yii\base\Model::load()]] method.


## Saving embedded models <span id="saving-embedded-models"></span>

Keep in mind that embedded models are stored separately from the source model attributes. You will need to use
[[\yii2tech\embedded\ContainerInterface::refreshFromEmbedded()]] method in order to populate source model
attributes with the data from embedded models.

Also note, that attempt to get 'dirty' value for embedded source attribute will also fail until you use `refreshFromEmbedded()`
even, if embedded model has changed:

```php
$user = User::findOne(1); // declares embedded model 'contactModel' from attribute 'contactData'

if ($user->contactModel->load(Yii::$app->request->post())) {
    var_dump($user->isAttributeChanged('contactData')); // outputs `false`

    $user->refreshFromEmbedded();
    var_dump($user->isAttributeChanged('contactData')); // outputs `true`
}
```

In case you are applying 'embedded' functionality to an ActiveRecord class, the best place for the data synchronization
is [[\yii\db\BaseActiveRecord::beforeSave()]] method. For example, application of this extension to the [[\yii\mongodb\ActiveRecord]]
class may look like following:

```php
use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

class ActiveRecord extends \yii\mongodb\ActiveRecord implements ContainerInterface
{
    use ContainerTrait;

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->refreshFromEmbedded(); // populate this model attributes from embedded models' ones, ensuring they are marked as 'changed' before saving
        return true;
    }
}
```


## Predefined model classes <span id="predefined-model-classes"></span>

This extension is generic and may be applied to any model with complex attributes. However, to simplify integration with
common solutions several base classes are provided by this extension:
 - [[\yii2tech\embedded\mongodb\ActiveRecord]] - MongoDB ActiveRecord with embedded feature built-in
 - [[\yii2tech\embedded\mongodb\ActiveRecordFile]] - MongoDB GridFS ActiveRecord with embedded feature built-in
 - [[\yii2tech\embedded\elasticsearch\ActiveRecord]] - ElasticSearch ActiveRecord with embedded feature built-in

Provided ActiveRecord classes already implement [[\yii2tech\embedded\ContainerInterface]] and invoke `refreshFromEmbedded()`
on `beforeSave()` stage.
For example, if you are using MongoDB and wish to work with sub-documents, you may simply switch extending from
regular [[\yii\mongodb\ActiveRecord]] to [[\yii2tech\embedded\mongodb\ActiveRecord]]:

```php
class User extends \yii2tech\embedded\mongodb\ActiveRecord
{
    public static function collectionName()
    {
        return 'customer';
    }

    public function attributes()
    {
        return ['_id', 'name', 'email', 'addressData', 'status'];
    }

    public function embedAddress()
    {
        return $this->mapEmbedded('addressData', UserAddress::className());
    }
}
```
