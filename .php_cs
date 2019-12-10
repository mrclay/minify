<?php declare(strict_types=1);

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules(
        [
            'align_multiline_comment'                       => [
                'comment_type' => 'all_multiline',
            ],
            'array_indentation'                             => true,
            'array_syntax'                                  => [
                'syntax' => 'long',
            ],
            'backtick_to_shell_exec'                        => true,
            'binary_operator_spaces'                        => [
                'operators' => ['=>' => 'align_single_space_minimal'],
            ],
            'blank_line_after_namespace'                    => true,
            'blank_line_after_opening_tag'                  => false,
            'blank_line_before_statement'                   => true,
            'braces'                                        => true,
            'cast_spaces'                                   => [
                'space' => 'single',
            ],
            'class_attributes_separation'                   => true,
            'class_keyword_remove'                          => false,
            'combine_consecutive_issets'                    => true,
            'combine_consecutive_unsets'                    => true,
            'combine_nested_dirname'                        => true,
            // 'compact_nullable_typehint'                  => true, // PHP >= 7.1
            'concat_space'                                  => [
                'spacing' => 'one',
            ],
            'date_time_immutable'                           => false,
            'declare_equal_normalize'                       => true,
            'declare_strict_types'                          => false, // some tests need declare_strict_types === 0
            'dir_constant'                                  => true,
            'elseif'                                        => true,
            'encoding'                                      => true,
            'ereg_to_preg'                                  => true,
            'error_suppression'                             => false,
            'escape_implicit_backslashes'                   => false,
            'explicit_indirect_variable'                    => true,
            'explicit_string_variable'                      => true,
            'final_internal_class'                          => true,
            'fopen_flag_order'                              => true,
            'fopen_flags'                                   => true,
            'full_opening_tag'                              => true,
            'fully_qualified_strict_types'                  => false, // maybe better for readability, so keep it ...
            'function_declaration'                          => true,
            'function_to_constant'                          => true,
            'function_typehint_space'                       => true,
            'general_phpdoc_annotation_remove'              => [
                'annotations' => [
                    'author',
                    'package',
                    'version',
                ],
            ],
            'heredoc_to_nowdoc'                             => false,
            'implode_call'                                  => false,
            'include'                                       => true,
            'increment_style'                               => false, // maybe better for readability, so keep it ...
            'indentation_type'                              => true,
            'line_ending'                                   => true,
            'linebreak_after_opening_tag'                   => false,
            /*                                                         // Requires PHP >= 7.1
            'list_syntax'                                   => [
                'syntax' => 'short',
            ],
            */
            'logical_operators'                             => true,
            'lowercase_cast'                                => true,
            'lowercase_constants'                           => true,
            'lowercase_keywords'                            => true,
            'lowercase_static_reference'                    => true,
            'magic_constant_casing'                         => true,
            'magic_method_casing'                           => true,
            'method_argument_space'                         => [
                'ensure_fully_multiline'           => true,
                'keep_multiple_spaces_after_comma' => false,
            ],
            'method_chaining_indentation'                   => false, // maybe better for readability, so keep it ...
            'modernize_types_casting'                       => true,
            'multiline_comment_opening_closing'             => false, // maybe better for readability, so keep it ...
            'multiline_whitespace_before_semicolons'        => [
                'strategy' => 'no_multi_line',
            ],
            'native_constant_invocation'                    => true,
            'native_function_casing'                        => true,
            'native_function_invocation'                    => true,
            'new_with_braces'                               => true,
            'no_alias_functions'                            => true,
            'no_alternative_syntax'                         => true,
            'no_binary_string'                              => true,
            'no_blank_lines_after_class_opening'            => false,
            'no_blank_lines_after_phpdoc'                   => true,
            'no_blank_lines_before_namespace'               => false,
            'no_break_comment'                              => true,
            'no_closing_tag'                                => true,
            'no_empty_comment'                              => true,
            'no_empty_phpdoc'                               => true,
            'no_empty_statement'                            => true,
            'no_extra_blank_lines'                          => true,
            'no_homoglyph_names'                            => true,
            'no_leading_import_slash'                       => true,
            'no_leading_namespace_whitespace'               => true,
            'no_mixed_echo_print'                           => [
                'use' => 'echo',
            ],
            'no_multiline_whitespace_around_double_arrow'   => true,
            'no_null_property_initialization'               => true,
            'no_php4_constructor'                           => true,
            'no_short_bool_cast'                            => true,
            'no_short_echo_tag'                             => true,
            'no_singleline_whitespace_before_semicolons'    => true,
            'no_spaces_after_function_name'                 => true,
            'no_spaces_around_offset'                       => true,
            'no_spaces_inside_parenthesis'                  => true,
            'no_superfluous_elseif'                         => false, // maybe better for readability, so keep it ...
            'no_superfluous_phpdoc_tags'                    => false, // maybe add extra description, so keep it ...
            'no_trailing_comma_in_list_call'                => true,
            'no_trailing_comma_in_singleline_array'         => true,
            'no_trailing_whitespace'                        => true,
            'no_trailing_whitespace_in_comment'             => true,
            'no_unneeded_control_parentheses'               => true,
            'no_unneeded_curly_braces'                      => true,
            'no_unneeded_final_method'                      => true,
            'no_unreachable_default_argument_value'         => false, // do not changes the logic of the code ...
            'no_unset_on_property'                          => true,
            'no_unused_imports'                             => true,
            'no_useless_else'                               => true,
            'no_useless_return'                             => true,
            'no_whitespace_before_comma_in_array'           => true,
            'no_whitespace_in_blank_line'                   => true,
            'non_printable_character'                       => true,
            'normalize_index_brace'                         => true,
            'not_operator_with_space'                       => false,
            'not_operator_with_successor_space'             => false,
            'object_operator_without_whitespace'            => true,
            'ordered_class_elements'                        => false, // maybe better for readability, so keep it ...
            'ordered_imports'                               => true,
            'phpdoc_add_missing_param_annotation'           => [
                'only_untyped' => true,
            ],
            'phpdoc_align'                                  => false, // maybe better for readability for very long names, so keep it ...
            'phpdoc_annotation_without_dot'                 => true,
            'phpdoc_indent'                                 => true,
            'phpdoc_inline_tag'                             => true,
            'phpdoc_no_access'                              => true,
            'phpdoc_no_alias_tag'                           => true,
            'phpdoc_no_empty_return'                        => false, // maybe better for readability, so keep it ...
            'phpdoc_no_package'                             => true,
            'phpdoc_no_useless_inheritdoc'                  => true,
            'phpdoc_order'                                  => true,
            'phpdoc_return_self_reference'                  => true,
            'phpdoc_scalar'                                 => true,
            'phpdoc_separation'                             => true,
            'phpdoc_single_line_var_spacing'                => true,
            'phpdoc_summary'                                => false,
            'phpdoc_to_comment'                             => false,
            'phpdoc_to_return_type'                         => false,
            'phpdoc_trim'                                   => true,
            'phpdoc_trim_consecutive_blank_line_separation' => true,
            'phpdoc_types'                                  => true,
            'phpdoc_types_order'                            => [
                'null_adjustment' => 'always_last',
                'sort_algorithm'  => 'alpha',
            ],
            'phpdoc_var_without_name'                       => true,
            'php_unit_construct'                            => true,
            'php_unit_dedicate_assert'                      => true,
            'php_unit_expectation'                          => true,
            'php_unit_fqcn_annotation'                      => true,
            'php_unit_internal_class'                       => true,
            'php_unit_method_casing'                        => true,
            'php_unit_mock'                                 => true,
            'php_unit_namespaced'                           => true,
            'php_unit_no_expectation_annotation'            => true,
            'php_unit_ordered_covers'                       => true,
            'php_unit_set_up_tear_down_visibility'          => true,
            'php_unit_strict'                               => true,
            'php_unit_test_annotation'                      => true,
            'php_unit_test_case_static_method_calls'        => true,
            'php_unit_test_class_requires_covers'           => false,
            'pow_to_exponentiation'                         => true,
            'pre_increment'                                 => false,
            'protected_to_private'                          => true,
            'return_assignment'                             => true,
            'return_type_declaration'                       => true,
            // 'self_accessor'                                 => true, // PHP >= 5.4
            'semicolon_after_instruction'                   => true,
            'set_type_to_cast'                              => true,
            'short_scalar_cast'                             => true,
            'silenced_deprecation_error'                    => false,
            'simplified_null_return'                        => false, // maybe better for readability, so keep it ...
            'single_blank_line_at_eof'                      => true,
            'single_class_element_per_statement'            => true,
            'single_import_per_statement'                   => true,
            'single_line_after_imports'                     => true,
            'single_line_comment_style'                     => [
                'comment_types' => ['hash'],
            ],
            'single_quote'                                  => true,
            'space_after_semicolon'                         => true,
            'standardize_increment'                         => false, // maybe better for readability, so keep it ...
            'standardize_not_equals'                        => true,
            // 'static_lambda'                                 => true, // PHP >= 5.4
            'strict_comparison'                             => true,
            'strict_param'                                  => true,
            'string_line_ending'                            => true,
            'switch_case_semicolon_to_colon'                => true,
            'switch_case_space'                             => true,
            'ternary_operator_spaces'                       => true,
            // 'ternary_to_null_coalescing'                    => true, // PHP >= 7.0
            'trailing_comma_in_multiline_array'             => true,
            'trim_array_spaces'                             => true,
            'unary_operator_spaces'                         => true,
            'visibility_required'                           => true,
            // 'void_return'                                   => true, // PHP >= 7.1
            'whitespace_after_comma_in_array'               => true,
            'yoda_style'                                    => [
                'equal'            => false,
                'identical'        => false,
                'less_and_greater' => false,
            ],
        ]
    )
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(['.', 'builder/', 'lib/', 'tests/', 'min_extras/', 'static/'])
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );