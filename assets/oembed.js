(function($){
    $(document).ready(function(){
        var videos = $("iframe[src*='//www.youtube.com'].fluid, iframe[src*='//player.vimeo.com'].fluid");

        videos.each(function(){
            $(this).data( 'aspectRatio', (this.height / this.width) )
                   .removeAttr('height')
                   .removeAttr('width')
                   .css('width', '100%' );
        });

        $(window).resize(function(){
            videos.each(function(){
                $(this).height(
                    $(this).width() * $(this).data('aspectRatio')
                );
            });
        }).trigger('resize');
    });
}(jQuery));
