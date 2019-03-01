<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $ItemsDataProvider yii\data\ArrayDataProvider */
/* @var $VersionsDataProvider yii\data\ArrayDataProvider */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $fs app\models\RdiffFileSystem the Rdiff data model */
/* @var $date string the date */
/* @var $options array RdiffbackupFilesystem options array */
/* @var $q string backup search query */

$js = <<< 'SCRIPT'
/* To initialize BS3 tooltips set this below */
$(function () { 
    $("[data-toggle='tooltip']").tooltip(); 
});;
/* To initialize BS3 popovers set this below */
$(function () { 
    $("[data-toggle='popover']").popover(); 
});

$(document).on('click', '.special-dropdown .dropdown-menu .pagination a', function (e) {
  e.stopPropagation();
});

$('#confirmRestore').on('show.bs.modal', function(e) {
    $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
    $('#confirmRestoreItemPath').text($(e.relatedTarget).data('path')).html();
    $('#confirmRestoreItemDate').html($(e.relatedTarget).data('version'));
});

$(document).on('click', '#confirmRestore a#restore-now', function (e) {
  $('#confirmRestore').modal('hide');
  $('.nav-tabs a[href*="restores"]').tab('show');
});


//$(document).on('click', '.special-dropdown .dropdown-menu #versionLinks', function (e) {
//  $.pjax({url: this.href, container: '#browse', push: false});
//});

//$('.special-dropdown a[id="versionLinks"]').on('click', function (e) {
//    $.pjax({url: this.href, container: '#browse', push: false});
//    e.stopPropagation();
//});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$ajax = <<< 'SCRIPT'
(function($){
  $.deparam = $.deparam || function(uri, data){
    if(uri === undefined){
      uri = window.location.search;
    }
    var queryString = {};
    uri.replace(
      new RegExp("([^?=&]+)(=([^&#]*))?", "g"),
      function($0, $1, $2, $3) { queryString[$1] = $3; }
    );

    var ret = data || [];
    Object.keys(queryString).map(function(key, index) {
      const container = {};
      container["name"] = key;
      container["value"] = queryString[key];
      ret.push(container);
    });

    return ret;

  };
})(jQuery);

$("form#backup-search-form").on('beforeSubmit', function(event) {
    event.preventDefault(); // stopping submitting
    var data = $(this).serializeArray();
    //var url = $(this).attr('action');
    var url = $(location).attr('href');
    var method = $(this).attr('method');
    var container = $(this).closest('[data-pjax-container]')[0];
    arr = $.deparam(url, data);
    console.log(arr, data);


    $.pjax({url: url, data: data, type: method, container: "#" + container.id, async:false});
    return false;

});
SCRIPT;
// Register ajax submit javascript
$this->registerJs($ajax, \yii\web\View::POS_READY);

?>

<div class="panel panel-default">
  <div class="panel-heading">
    <div class="row">
      <div class="col-sm-8 backup-browse-item">

        <div class="">
        <?= Html::a(
            '<span class="glyphicon glyphicon-' . ($options['showDotFiles'] ? 'check' : 'unchecked') . '"></span> Show hidden files',
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => $fs->path,
                'date' => $date,
                'showDotFiles' => !$options['showDotFiles'],
                '#' => 'browse'
            ])
        ); ?>
        </div>

<?php

        echo "<span>Path</span>: " . Html::a(
            '<span class="glyphicon glyphicon-folder-open"></span> ' . $fs->root,
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => '/',
                'date' => $date,
                'showDotFiles' => $options['showDotFiles'],
                '#' => 'browse',
            ])
        );

        $i = '';
        foreach (preg_split("/\//", $fs->path, 0, PREG_SPLIT_NO_EMPTY) as $el) {
            $i .= '/' . $el;
            echo "/" . Html::a(
                $el,
                Url::to([
                    false,
                    'id' => $ticket->id,
                    'path' => $i,
                    'date' => $date,
                    'showDotFiles' => $options['showDotFiles'],
                    '#' => 'browse',
                ])
            );
        }

        if ($date != "all") {
          echo "&nbsp;&nbsp;&nbsp;";
          echo "<span class='backup-browse-options'>";
          echo Html::a(
              '<span class="glyphicon glyphicon-tasks"></span> Restore this state of the directory',
              Url::to([
                  'ticket/restore',
                  'id' => $ticket->id,
                  'file' => $fs->path,
                  'date' => $fs->version,
                  'showDotFiles' => $options['showDotFiles'],
              ]),
              [
                  'data-href' => Url::to([
                      'ticket/restore',
                      'id' => $ticket->id,
                      'file' => $fs->path,
                      'date' => $fs->version,
                      'showDotFiles' => $options['showDotFiles'],
                      '#' => 'tab_restores',
                  ]),
                  'data-toggle' => 'modal',
                  'data-target' => '#confirmRestore',
                  'data-path' => $fs->path,
                  'data-version' => yii::$app->formatter->format($fs->version, 'backupVersion'),
              ]
          );    
          echo ")</span>";
        }

?>

      </div>
      <div class="col-sm-4">

        <?php $form = ActiveForm::begin([
          'id' => 'backup-search-form',
          'action' => [ 'ticket/view' ],
          'method' => 'get',
        ]); ?>
          <div class="col-sm-12 input-group input-group-sm">
            <span class="input-group-addon" id="search-addon" style="min-width:70px;">Search</span>
            <?= Html::input("text", "q", $q, [
              'class' => 'form-control',
              'id' => 'q',
              'placeholder' => 'Ex: file.pdf',
              'aria-describedby' => 'search-addon',
              'style' => 'width:100%;'
            ]); ?>
          </div>
        <?php ActiveForm::end(); ?>
        <div class="input-group input-group-sm col-sm-12 pull-right special-dropdown">
          <span class="input-group-addon" id="version-addon" style="min-width:70px;">Version</span>
          <span class="input-group-btn" style="width:100%;">
            <button style="width:100%;" class="btn btn-default toggle-dropdown" data-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-describedby="version-addon"> <span class="glyphicon glyphicon-cog"></span> <?= $date == 'all' ? 'All versions overlapping' : (yii::$app->formatter->format($fs->version, 'datetime') . ($fs->version == $fs->newestBackupVersion ? ' (current)' : null)); ?></button>

            <?php Pjax::begin([
                'id' => 'w101',
                'options' => ['tag' => 'ul', 'class' => 'dropdown-menu dropdown-menu-right'],
            ]); ?>
            <?= ListView::widget([
                'dataProvider' => $VersionsDataProvider,
                'options' => [
                    'tag' => false,
                    //'class' => 'dropdown-menu dropdown-menu-right'
                ],
                /*'itemOptions' => function($model) {
                    return [
                        'tag'=> 'li',
                        'class' => 'list-group-item-success'
                    ];
                },*/
                'itemOptions' => [
                    'tag' => 'li',
                ],
                'viewParams' => ['ticket' => $ticket, 'fs' => $fs, 'date' => $date, 'options' => $options],
                'itemView' => '_browse_version',
                'emptyText' => 'No versions.',
                'emptyTextOptions' => [
                    'tag' => 'li',
                    'class' => 'empty dropdown-header',
                ],
                'layout' => '{items} {summary} {pager}',
                'summaryOptions' => [
                    'tag' => 'li',
                    'class' => 'summary dropdown-header',
                ],
                'pager' => [
                    'maxButtonCount' => 3,
                    'options' => [
                        'class' => 'dropdown-header pagination pagination-sm',
                        'style' => 'padding: 3px 20px;'
                    ]
                ],        
            ]); ?>
            <?php Pjax::end() ?>

          </span>
        </div>
      </div>
    </div>
    <p></p>
    <?= ListView::widget([
        'dataProvider' => $ItemsDataProvider,
        'options' => [
            'id' => 'w102',
            'tag' => 'div',
            'class' => 'list-group'
        ],
        'itemOptions' => [
            'tag' => 'div',
            'class' => 'list-group-item backup-browse-item div-hover',
        ],
        'itemView' => '_browse_item',
        'viewParams' => ['ticket' => $ticket, 'date' => $date, 'options' => $options],
        'emptyText' => 'No files or directories.',
        'emptyTextOptions' => [
            'tag' => 'div',
            'class' => 'empty list-group-item backup-browse-item',
        ],        
        'layout' => '{items} <div class="panel-footer">{summary}{pager}</div>',
        'summaryOptions' => [
            'tag' => 'div',
            'class' => 'summary',
        ],
        'pager' => [
            'options' => [
                'class' => 'pagination pagination-sm',
            ]
        ],
    ]); ?>
  </div>
</div>
