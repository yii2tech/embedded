<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\embedded;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * Validator validates embedded entities as nested models.
 * This validator may be applied only for the model, which implements [[ContainerInterface]] interface.
 *
 * ```php
 * class User extends Model implements ContainerInterface
 * {
 *     use ContainerTrait;
 *
 *     public $contactData;
 *
 *     public function embedContact()
 *     {
 *         return $this->mapEmbedded('contactData', Contact::className());
 *     }
 *
 *     public function rules()
 *     {
 *         return [
 *             ['contact', 'yii2tech\embedded\Validator'],
 *         ]
 *     }
 * }
 *
 * class Contact extends Model
 * {
 *     public $email;
 *
 *     public function rules()
 *     {
 *         return [
 *             ['email', 'required'],
 *             ['email', 'email'],
 *         ]
 *     }
 * }
 * ```
 *
 * > Note: pay attention that this validator must be set for the embedded model name - not for its source attribute.
 * Do not mix them up!
 *
 * @see ContainerInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Validator extends \yii\validators\Validator
{
    /**
     * @var bool whether to add an error message to embedded source attribute instead of embedded name itself.
     */
    public $addErrorToSource = true;
    /**
     * @var bool whether to run validation only in case embedded model(s) has been already initialized (requested as
     * object at least once). This option is disabled by default.
     *
     * @see Mapping::getIsValueInitialized()
     * 
     * @since 1.0.1
     */
    public $initializedOnly = false;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        if (!($model instanceof ContainerInterface)) {
            throw new InvalidConfigException('Owner model must implement "yii2tech\embedded\ContainerInterface" interface.');
        }

        $mapping = $model->getEmbeddedMapping($attribute);

        if ($this->initializedOnly && !$mapping->getIsValueInitialized()) {
            return;
        }

        $embedded = $model->getEmbedded($attribute);

        if ($mapping->multiple) {
            if (!is_array($embedded) && !($embedded instanceof \IteratorAggregate)) {
                $error = $this->message;
            } else {
                foreach ($embedded as $embeddedModel) {
                    if (!($embeddedModel instanceof Model)) {
                        throw new InvalidConfigException('Embedded object "' . get_class($embeddedModel) . '" must be an instance or descendant of "' . Model::className() . '".');
                    }
                    if (!$embeddedModel->validate()) {
                        $error = $this->message;
                    }
                }
            }
        } else {
            if (!($embedded instanceof Model)) {
                throw new InvalidConfigException('Embedded object "' . get_class($embedded) . '" must be an instance or descendant of "' . Model::className() . '".');
            }
            if (!$embedded->validate()) {
                $error = $this->message;
            }
        }

        if (!empty($error)) {
            $this->addError($model, $this->addErrorToSource ? $mapping->source : $attribute, $error);
        }
    }
} 