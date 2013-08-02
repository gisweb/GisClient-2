<?php
$TABLES=array(

"e_outputformat"=>array(
	"outputformat_name"=>"name",
	"outputformat_def"=>"txt",
	"outputformat_id"=>"outputformat_id",
),

"e_fieldformat"=>array(
	"fieldformat_name"=>"description",
	"fieldformat_format"=>"field_format",
	"fieldformat_id"=>"fieldformat_id",
	"fieldformat_order"=>"\"order\""
),

"font"=>array(
	"file_name"=>"file",
	"font_name"=>"font_name"
),

"symbol"=>array(
	"symbolcategory_id"=>1,
	"symbol_def"=>"def",
	//"symbol_image"=>"symbol_image",
	"symbol_name"=>"symbol_name",
	"icontype"=>"(symboltype_id-1)"
),

"symbol_ttf"=>array(
	"ascii_code"=>"ascii_code",
	"font_name"=>"font",
	"position"=>"position",
	"symbolcategory_id"=>1,
	//"symbol_ttf_image"=>"symbol_ttf_image",
	"symbol_ttf_name"=>"symbol_name",
),

"project"=>array(
	"base_path"=>"base_path",
	"base_url"=>"base_url",
	"project_extent"=>"extent",
	"history"=>"history",
	"icon_h"=>"icon_h",
	"icon_w"=>"icon_w",
	"imagelabel_color"=>"imagelabel_color",
	"imagelabel_font"=>"imagelabel_font",
	"imagelabel_offset_x"=>"imagelabel_offset_x",
	"imagelabel_offset_y"=>"imagelabel_offset_y",
	"imagelabel_position"=>"imagelabel_position",
	"imagelabel_size"=>"imagelabel_size",
	"imagelabel_text"=>"imagelabel_text",
	"project_name"=>"project_name",
	"project_title"=>"project_name",
	"project_description"=>"description",
	"project_srid"=>"project_srid",
	"sel_transparency"=>"sel_transparency",
	"sel_user_color"=>"sel_user_color",
	"project_note"=>"note"
),

"catalog"=>array(
	"catalog_id"=>"catalog_id",
	"catalog_name"=>"catalog_name",
	"connection_type"=>"case when dbschema is null then 1 else 6 end",
	"project_name"=>"(select project_name from $DBSCHEMA_OLD.project where project_id=catalog.project_id)",
	"catalog_path"=>"case when dbschema is null then shape_dir else (select dbname||'/'|| catalog.dbschema from $DBSCHEMA_OLD.connection where connection_id=catalog.connection_id) end",
	"catalog_url"=>"doc_path",
	"catalog_description"=>"description"
),

"theme"=>array(
	"project_name"=>"(select project_name from $DBSCHEMA_OLD.project where project_id=theme.project_id)",
	"theme_id"=>"theme_id",
	"theme_name"=>"theme_name",
	"theme_order"=>"theme_order",
	"theme_title"=>"theme_title"
),

"layergroup"=>array(
	"layergroup_id"=>"layergroup_id",
	"layergroup_maxscale"=>"layergroup_maxscale",
	"layergroup_minscale"=>"layergroup_minscale",
	"layergroup_name"=>"layergroup_name",
	"layergroup_order"=>"layergroup_order",
	"layergroup_smbscale"=>"layergroup_smbscale",
	"layergroup_title"=>"layergroup_title",
	"theme_id"=>"theme_id"
),

"layer"=>array(
	"catalog_id"=>"coalesce(catalog_id,-1)",
	"classitem"=>"classitem",
	"data"=>"data",
	"data_filter"=>"data_filter",
	"data_geom"=>"data_geom",
	"data_srid"=>"data_srid",
	"data_unique"=>"data_unique",
	"labelitem"=>"labelitem",
	"labelmaxscale"=>"labelmaxscale",
	"labelminscale"=>"labelminscale",
	"labelrequires"=>"labelrequires",
	"layer_def"=>"layer_def",
	"layergroup_id"=>"layergroup_id",
	"layer_id"=>"layer_id",
	"layer_name"=>"layer_name",
	"layer_order"=>"layer_order",
	"layertype_id"=>"layertype_id",
	"mapset_filter"=>"(select coalesce(mapset_filter,0) from $DBSCHEMA_OLD.layergroup where layergroup_id=layer.layergroup_id)",
	"maxfeatures"=>"maxfeatures",
	"maxscale"=>"maxscale",
	"minscale"=>"minscale",
	"requires"=>"requires",
	"sizeunits_id"=>"sizeunits_id",
	"symbolscale"=>"symbolscale",
	"transparency"=>"transparency"
),

"class"=>array(
	"class_id"=>"class_id",
	"class_image"=>"class_image",
	"class_name"=>"class_name",
	"class_order"=>"class_order",
	"class_text"=>"class_text",
	"class_title"=>"class_title",
	"expression"=>"expression",
	"label_angle"=>"label_angle",
	"label_antialias"=>"label_antialias",
	"label_bgcolor"=>"label_bgcolor",
	"label_color"=>"label_color",
	"label_def"=>"label_def",
	"label_font"=>"label_font",
	"label_free"=>"label_free",
	"label_maxsize"=>"label_maxsize",
	"label_minsize"=>"label_minsize",
	"label_outlinecolor"=>"label_outlinecolor",
	"label_position"=>"label_position",
	"label_priority"=>"label_priority",
	"label_size"=>"label_size",
	"label_wrap"=>"label_wrap",
	"layer_id"=>"layer_id",
	"legendtype_id"=>"case when legend=1 then 1 else 0 end",
	"maxscale"=>"maxscale",
	"minscale"=>"minscale",
	"symbol_ttf_name"=>"symbol_ttf_id"
),

"style"=>array(
	"angle"=>"angle",
	"bgcolor"=>"bgcolor",
	"class_id"=>"class_id",
	"color"=>"color",
	"maxsize"=>"maxsize",
	"maxwidth"=>"maxwidth",
	"minsize"=>"minsize",
	"minwidth"=>"minwidth",
	"outlinecolor"=>"outlinecolor",
	"size"=>"size::text",
	"style_def"=>"style_def",
	"style_id"=>"style_id",
	"style_name"=>"style_name",
	"style_order"=>"style_order",
	"symbol_name"=>"symbol_name",
	"width"=>"width"
),

"link"=>array(
	"link_id"=>"link_id",
	"link_def"=>"link",
	"link_name"=>"link_name",
	"link_order"=>"link_order",
	"project_name"=>"(select project_name from $DBSCHEMA_OLD.project where project_id=link.project_id)",
	"winh"=>"winh",
	"winw"=>"winw"
),

"qt"=>array(
	"layer_id"=>"layer_id",
	"max_rows"=>"max_rows",
	"papersize_id"=>"papersize_id",
	"qt_id"=>"qt_id",
	"qt_name"=>"qt_name",
	"qt_order"=>"qt_order",
	"theme_id"=>"theme_id"
),

"qtrelation"=>array(
	"catalog_id"=>"catalog_id",
	"data_field_1"=>"data_field_1",
	"data_field_2"=>"data_field_2",
	"data_field_3"=>"data_field_3",
	"qt_id"=>"qt_id",
	"qtrelation_id"=>"qtrelation_id",
	"qtrelation_name"=>"qtrelation_name",
	"qtrelationtype_id"=>"case when qtrelationtype_id=1 then 1 else 2 end",
	"table_field_1"=>"table_field_1",
	"table_field_2"=>"table_field_2",
	"table_field_3"=>"table_field_3",
	"table_name"=>"table_name"
),

"qtfield"=>array(
	"column_width"=>"width",
	"field_format"=>"field_format",
	"field_header"=>"field_header",
	"fieldtype_id"=>"case when fieldtype_id=4 then 10 when fieldtype_id=6 then 101 else fieldtype_id end",
	"qtfield_id"=>"qtfield_id",
	"qtfield_name"=>"qtfield_name",
	"qtfield_order"=>"qtfield_order",
	"qt_id"=>"qt_id",
	"qtrelation_id"=>"qtrelation_id",
	"resultype_id"=>"resultype_id",
	"searchtype_id"=>"searchtype_id"
),

"mapset"=>array(
	"bg_color"=>"bg_color",
	"dl_image_res"=>"gtiff_dpi",
	"filter_data"=>"filter_data",
	"imagelabel"=>"imagelabel",
	"interlace"=>"interlace",
	"legend_icon_h"=>"legend_icon_h",
	"legend_icon_w"=>"legend_icon_w",
	"mapset_def"=>"mapset_def",
	"mapset_extent"=>"mapset_extent",
	"mapset_name"=>"mapset_name",
	"mapset_srid"=>"projection::integer",
	"mask"=>"mask",
	"outputformat_id"=>"outputformat_id",
	"page_size"=>"page_size",
	"project_name"=>"(select project_name from $DBSCHEMA_OLD.project where project_id=mapset.project_id)",
	"refmap_extent"=>"refmap_extent",
	"template"=>"'$TEMPLATE'",
	"test_extent"=>"test_extent",
	"mapset_title"=>"title",
	"private"=>1
),

"mapset_layergroup"=>array(
	"layergroup_id"=>"layergroup_id",
	"mapset_name"=>"(select mapset_name from $DBSCHEMA_OLD.mapset where mapset_id=mapset_layergroup.mapset_id)",
	"refmap"=>"refmap",
	"status"=>"status"
),

"mapset_link"=>array(
	"link_id"=>"link_id",
	"mapset_name"=>"(select mapset_name from $DBSCHEMA_OLD.mapset where mapset_id=mapset_link.mapset_id)"
),

"mapset_qt"=>array(
	"mapset_name"=>"(select mapset_name from $DBSCHEMA_OLD.mapset where mapset_id=mapset_qt.mapset_id)",
	"qt_id"=>"qt_id"
),


"qt_link"=>array(
	"link_id"=>"link_id",
	"qt_id"=>"qt_id",
	"resultype_id"=>"resultype_id"
),


"selgroup"=>array(
	"project_name"=>"(select project_name from $DBSCHEMA_OLD.project where project_id=selgroup.project_id)",
	"selgroup_id"=>"selgroup_id",
	"selgroup_name"=>"selgroup_name",
	"selgroup_order"=>"selgroup_order"
),

"qt_selgroup"=>array(
	"qt_id"=>"qt_id",
	"selgroup_id"=>"selgroup_id"
)


);?>