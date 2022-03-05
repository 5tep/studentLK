<?php

use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = Yii::$app->name;

$this->registerCssFile('css\student_index_style.css', ['depends' => ['frontend\assets\FrontendAsset']]);
?>
<div class="site-index">
    <div class="body-content">
        <?php if (!empty($routes)): ?>
            <?php $i = 0;
            foreach ($routes as $route => $link_name): ?>
                <?php if ($i % 2 == 0): ?>
                    <div class="row">
                <?php endif; ?>
                <div class="col-xs-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <a href="<?php echo Url::toRoute($route); ?>"><?php echo $link_name; ?></a>
                        </div>
                    </div>
                </div>
                <?php if ($i % 2 !== 0 || $i == (sizeof($routes) - 1)): ?>
                    </div>
                <?php endif; ?>
            <?php $i++;
            endforeach; ?>
        <?php endif; ?>
    </div>
</div>
