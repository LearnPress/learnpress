<?php
ob_start();
?>
    <style type="text/css">
        #wrapper {
            color: #777;
        }

        #template_container {
            background: #f2fbff;
        }

        #template_header_image {
            text-align: center;
        }

        .order-heading {
            background: #00adff;
            padding: 20px;
            color: #FFF;
            margin: 0 0 20px 0;
            font-weight: lighter;
            font-size: 24px;
            border-radius: 3px;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
        }

        .order-details {
            width: 100%;
            margin-bottom: 20px;
        }

        .order-details th {
            text-align: left;
            font-weight: normal;
            padding: 5px 0;
        }

        .order-details td {
            text-align: right;
            padding: 5px 0;
        }

        .order-table-items-heading {
            background: #dedede;
            color: #656565;
            text-align: center;
            padding: 10px;
            text-transform: uppercase;
            font-weight: normal;
            border-radius: 3px;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
        }

        .order-table-items {
            width: 100%;
            font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
            border: none;
            font-size: 14px;
        }

        .order-table-items th {
            border-top: 1px solid #DDD;
        }

        .order-table-items th,
        .order-table-items td {
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #DDD;
            padding: 8px 0;
        }

        .order-table-items .column-name {

        }

        .order-table-items .column-quantity {
            width: 100px;
            text-align: right;
        }

        .order-table-items .column-number {
            width: 100px;
            text-align: right;
        }

        #email-body > tr > td {
            padding: 0 20px 20px 20px;
        }

        #template_container #email-footer td {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #DDD;
        }

        .order-heading-total {
            display: block;
            font-size: 32px;
        }

        p {
            margin: 0 0 20px 0;
        }

    </style>
<?php echo preg_replace( '!</?style.*>!', '', ob_get_clean() );
