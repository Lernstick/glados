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
        <td colspan="3" class="col-md-12">
            <p>
                {% if (file.url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                {% } else { %}
                    <span class="glyphicon glyphicon-file"></span>&nbsp;<span class="name">{%=file.name%}</span> (<span class="size">{%=o.formatFileSize(file.size)%}</span>)
                    <input name="hash" type="hidden" value="{%=file.name%}">
                {% } %}
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger"><?= Yii::t('jqueryfileupload', 'Error') ?></span> {%=file.error%}</div>
            {% } %}
        </td>
    </tr>
{% } %}

</script>
