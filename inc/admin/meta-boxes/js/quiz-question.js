/**
 * Created by Tu on 30/03/2015.
 */
;(function($){
return
var $doc = $(document),
    $body = $(document.body);
function addNewQuestion(){
    var type = $('#lpr-quiz-question-type').val();
    if( !type ){
        // warning
        return;
    }
    var data = {
        action: 'lpr_quiz_question_add',
        quiz_id: lpr_quiz_id,
        type: type
    };
    $.post(ajaxurl, data, function(res){
        var $question = $(res)
        $('#lpr-quiz-questions').append($question);
        lprHook.doAction('lpr_admin_quiz_question_html', $question, type);

        $('#lpr-quiz-question-type').val('')
    }, 'text');
}
function _ready(){
    $('#lpr-quiz-question-add').click(addNewQuestion);
}

$doc.ready(_ready);

})(jQuery)