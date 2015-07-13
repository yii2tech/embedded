<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\embedded\elasticsearch;

use yii2tech\embedded\ContainerInterface;
use yii2tech\embedded\ContainerTrait;

/**
 * ActiveRecord is an enhanced version of [[\yii\elasticsearch\ActiveRecord]], which includes 'embedded' functionality.
 *
 * Obviously, this class requires [yiisoft/yii2-elasticsearch](https://github.com/yiisoft/yii2-elasticsearch) extension installed.
 *
 * @see \yii\elasticsearch\ActiveRecord
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ActiveRecord extends \yii\elasticsearch\ActiveRecord implements ContainerInterface
{
    use ContainerTrait;

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->refreshFromEmbedded();
        return true;
    }
}