Custom Shortcode for LearnDash with Category Filter

This custom shortcode extends the functionality of the LearnDash [ld_profile] shortcode by adding options to include or exclude specific course categories. Use this shortcode to better control which courses display on a profile page based on category selection.

Setup:

To enable this functionality, add the provided code to your theme’s functions.php file or use a PHP snippet manager plugin. Once active, you can use the shortcode [ld_profile_with_categories] on any page, just like [ld_profile].

Usage:

The shortcode behaves similarly to the original [ld_profile], with additional parameters for category inclusion or exclusion.

Including a Category: 

To display only courses from a specific category, use:


[ld_profile_with_categories include-category="46"]
Replace "46" with the desired category ID to filter courses by that category.

Excluding a Category: 

To display all courses except those from a specific category, use:


[ld_profile_with_categories exclude-category="46"]
Replace "46" with the category ID you want to exclude from the displayed courses.

Notes: 

The rest of the [ld_profile] functionality remains unchanged.
You can customize course visibility by combining the include-category and exclude-category attributes for flexible course display options.
