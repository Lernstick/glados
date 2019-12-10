<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="col-md-6">
            <p style="display:inline"><span class="glyphicon glyphicon-file"></span>&nbsp;<span class="name">{%=file.name%}</span> (<p class="size" style="display:inline"><?= Yii::t('jqueryfileupload', 'Processing') ?>...</p>)</p>
            <strong class="error text-danger"></strong>
        </td>
        <td class="col-md-4">
            <div class="hidden progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
        </td>
        <td class="col-md-2">
            {% if (!i && !o.options.autoUpload) { %}
                <button title="Start" class="btn btn-default start" disabled>
                    <i class="glyphicon glyphicon-upload"></i>
                    <span><?= Yii::t('jqueryfileupload', 'Start') ?></span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button title="Cancel" class="pull-right btn btn-default cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span><?= Yii::t('jqueryfileupload', 'Cancel') ?></span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>