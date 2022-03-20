$(function(){
    var  textarea =   $('.input_tips');
    /*    textarea.focus(function(){
     var id =  $(this).attr('args');
     var tip = $(this).attr('tip');
     if(tip ==1){
     textFocus(id);
     $(this).attr('tip','0')
     }
     });

     textarea.blur(function(){
     var id =  $(this).attr('args');
     var tip = $(this).attr('tip');
     if(tip == 0){
     textBlur(id);
     $(this).attr('tip','1')
     }
     });*/
    textarea.keydown(function(){
        var id =  $(this).attr('args');
        textChange(id);
    })
    textarea.keyup(function(){

        var id =  $(this).attr('args');
        textChange(id);
    })

    var reply_btn = $('.reply_btn');
    reply_btn.click(function(){
        var args = getArgs( $(this).attr('args'))
        var to_f_reply_id = args['to_f_reply_id'];
        $('#reply_'+to_f_reply_id).val('回复@'+args['to_username']+' ：');
        $('#submit_'+to_f_reply_id).attr('args',$(this).attr('args'));

    })

    var submitReply =  $('.submitReply');
    $(document).on('click', '.submitReply', function(){

        var args = getArgs( $(this).attr('args'));
        var to_f_reply_id = args['to_f_reply_id'];
        var post_id =  $(this).attr('post_id');
        var content =  $('#reply_'+to_f_reply_id).val();
        var to_reply_id= args['to_reply_id'];
        var to_uid= args['to_uid'];
        var url = '/onePlus/index.php?s=/Forum/LZL/doSendLZLReply.html';

        $.post(url, {post_id:post_id , to_f_reply_id:to_f_reply_id, to_reply_id:to_reply_id, to_uid:to_uid, content: content}, function(msg){
            if(msg.status) {
                op_success('回复成功','温馨提示');
                $('#lzl_reply_list_'+to_f_reply_id).load(U('Forum/LZL/lzlList&to_f_reply_id='+to_f_reply_id+'&page='+msg.info,'',true),function(){
                    ucard() })
                $('#reply_'+to_f_reply_id).val('');
            } else {
                op_error(msg.info,'温馨提示');
            }
        });

        this.preventDefault();
    })

    $('.reply_btn').click(function(){
        var args =  $(this).attr('args');
     $('#lzl_reply_div_'+args).toggle();
        this.preventDefault();
    })

    $('.show_textarea').click(function(){
        var args =  $(this).attr('args');
        $('#show_textarea_'+args).toggle();
        this.preventDefault();
    })


});


var getArgs  = function(uri) {
    if ( ! uri ) return {};
    var obj = {},
        args = uri.split( "&" ),
        l, arg;
    l = args.length;
    while ( l -- > 0 ) {
        arg = args[l];
        if ( ! arg ) {
            continue;
        }
        arg = arg.split( "=" );
        obj[arg[0]] = arg[1];
    }
    return obj;
};


function textChange(id) {
    if ($('#reply_' + id).val().length == 0) {
        $('#submit_' + id).removeClass('button_true')
    }
    else {
        $('#submit_' + id).addClass('button_true')
    }
}
function textFocus(id) {
    $('#textarea_' + id).animate({marginLeft: '50px'}, 'fast','', function () {
        $('#myavatar_' + id).show();
    });
}

function textBlur(id) {
    if ($('#reply_' + id).val().length == 0) {

        $('#myavatar_' + id).hide();
        $('#textarea_' + id).animate({marginLeft: '0px'}, "fast");
    }
}