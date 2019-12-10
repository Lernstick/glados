<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        {% if (file.thumbnailUrl) { %}
            <td>
                <span class="preview">
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                </span>
            </td>                    
        {% } %}
        <td class="col-md-6">
            <p>
                {% if (file.url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                {% } else { %}
                    <span class="glyphicon glyphicon-file"></span>&nbsp;<span class="name">{%=file.name%}</span> (<span class="size">{%=o.formatFileSize(file.size)%}</span>)
                {% } %}
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger"><?= Yii::t('jqueryfileupload', 'Error') ?></span> {%=file.error%}</div>
            {% } %}
        </td>

        <td colspan="2" class="col-md-6">
            {% if (file.deleteUrl) { %}
                <button title="Remove the file" class="pull-right btn btn-default delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                    <i class="glyphicon glyphicon-trash"></i>
                    <span><?= Yii::t('jqueryfileupload', 'Remove') ?></span>
                </button>
                <input type="checkbox" name="delete" value="1" class="toggle hidden">
            {% } else { %}
                <button class="pull-right btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span><?= Yii::t('jqueryfileupload', 'Cancel') ?></span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}

</script>
