--
-- Calendar Base Form Fields
--

INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_list`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'text', 'Background color', 'color_background', 'calendar-base', 'calendar-single', 'col-md-3', '/event/view/##ID##', '', 0, 1, 0, '', '', ''),
(NULL, 'text', 'Color Text', 'color_text', 'calendar-base', 'calendar-single', 'col-md-3', '/event/view/##ID##', '', 0, 1, 0, '', '', ''),
(NULL, 'text', 'Type', 'type', 'calendar-base', 'calendar-single', 'col-md-3', '/event/view/##ID##', '', 0, 1, 0, '', '', '');
