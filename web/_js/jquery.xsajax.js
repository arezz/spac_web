/*
**  jquery.xsajax.js -- jQuery plugin for Cross-Site AJAX-style Javascript loading
**  Copyright (c) 2007 Ralf S. Engelschall <rse@engelschall.com>
**  Licensed under GPL <http://www.gnu.org/licenses/gpl.txt>
**
**  $LastChangedDate$
**  $LastChangedRevision$
*/

(function($){
    if (   $.browser.safari
        || navigator.userAgent.match(/Konqueror/i)) {
        $.extend({
            _xsajax$node: [],
            _xsajax$nodes: 0
        });
    }
    $.extend({
        getScriptXS: function () {
            /* determine arguments */
            var arg = {
                'url':      null,
                'gc':       true,
                'cb':       null,
                'cb_args':  null
            };
            if (typeof arguments[0] == "string") {
                /* simple usage */
                arg.url = arguments[0];
                if (typeof arguments[1] == "function")
                    arg.cb = arguments[1];
            }
            else if (typeof arguments[0] == "object") {
                /* flexible usage */
                for (var option in arguments[0])
                    if (typeof arg[option] != "undefined")
                        arg[option] = arguments[0][option];
            }

            /* generate <script> node */
            var node =
                $(document.createElement('script'))
                .attr('type', 'text/javascript')
                .attr('src', arg.url);

            /* optionally apply event handler to <script> node for
               garbage collecting <script> node after loading and/or
               calling a custom callback function */
            var node_helper = null;
            if (arg.gc || arg.cb !== null) {
                var callback = function () {
                    if (arg.cb !== null) {
                        var args = arg.cb_args;
                        if (args === null)
                            args = [];
                        else if (!(   typeof args === "object"
                                   && args instanceof Array   ))
                            args = [ args ];
                        arg.cb.apply(this, args);
                    }
                    if (arg.gc)
                        $(this).remove();
                };
                if ($.browser.msie) {
                    /* MSIE doesn't support the "onload" event on
                       <script> nodes, but it at least supports an
                       "onreadystatechange" event instead. But notice:
                       according to the MSDN documentation we would have
                       to look for the state "complete", but in practice
                       for <script> the state transitions from "loading"
                       to "loaded". So, we check for both here... */
                    node.get(0).onreadystatechange = function () {
                        if (   this.readyState == "complete"
                            || this.readyState == "loaded"  )
                            callback.call(this);
                    };
                }
                else if (   $.browser.safari
                         || navigator.userAgent.match(/Konqueror/i)) {
                    /* Safari/WebKit and Konqueror/KHTML do not emit
                       _any_ events at all, but we can exploit the fact
                       that dynamically generated <script> DOM nodes
                       are executed in sequence (although the scripts
                       theirself are still loaded in parallel) */
                    $._xsajax$nodes++;
                    var helper =
                        'var ctx = jQuery._xsajax$node[' + $._xsajax$nodes + '];' +
                        'ctx.callback.call(ctx.node);' +
                        'setTimeout(function () {' +
                        '    jQuery(ctx.node_helper).remove();' +
                        '}, 100);';
                    node_helper =
                        $(document.createElement('script'))
                        .attr('type', 'text/javascript')
                        .text(helper);
                    $._xsajax$node[$._xsajax$nodes] = {
                        callback: callback,
                        node: node.get(0),
                        node_helper: node_helper.get(0)
                    };
                }
                else {
                    /* Firefox, Opera and other reasonable browsers can
                       use the regular "onload" event... */
                    $(node).load(callback);
                }
            }

            /* inject <script> node into <head> of document */
            $('head', document).append(node);

            /* optionally inject helper <script> node into <head>
               (Notice: we have to use a strange indirection via
               setTimeout() to insert this second <script> node here or
               at least Konqueror (and perhaps also Safari) for unknown
               reasons will not execute the first <script> node at all) */
            if (node_helper !== null) {
                setTimeout(function () {
                    $('head', document).append(node_helper)
                }, 100);
            }
        }
    });
})(jQuery);
