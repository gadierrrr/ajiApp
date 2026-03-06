<?php
/**
 * Migration script: Add all missing translation keys to en.php and es.php
 * Run: php scripts/add-translation-keys.php
 */

$enFile = __DIR__ . '/../inc/lang/en.php';
$esFile = __DIR__ . '/../inc/lang/es.php';

$en = require $enFile;
$es = require $esFile;

// =====================================================================
// BEACH SECTION - Report Modal
// =====================================================================
$beachKeysEn = [
    // Report Modal
    'report_modal_title' => 'Report Outdated Info',
    'report_help_text' => 'Help us keep beach information accurate! Let us know what\'s changed at :name.',
    'report_what_updating' => 'What needs updating?',
    'report_opt_conditions' => 'Beach conditions (sargassum, surf, wind)',
    'report_opt_amenities' => 'Amenities (parking, restrooms, etc.)',
    'report_opt_access' => 'Access or directions',
    'report_opt_safety' => 'Safety information',
    'report_opt_other' => 'Other',
    'report_details' => 'Details',
    'report_placeholder' => 'Tell us what\'s different from what\'s shown...',
    'report_submit' => 'Submit Report',
    'report_cancel' => 'Cancel',
    'report_select_issue' => 'Please select at least one issue to report.',
    'report_submitting' => 'Submitting...',
    'report_success' => 'Thank you! Your report has been submitted and will be reviewed soon.',
    'report_toast_success' => 'Report submitted. Thank you!',
    // Check-In Modal
    'checkin_modal_title' => 'Check In',
    'checkin_share_text' => 'Share what you\'re seeing at :name right now!',
    'checkin_crowded' => 'How crowded is it?',
    'checkin_empty' => 'Empty',
    'checkin_light' => 'Light',
    'checkin_moderate' => 'Moderate',
    'checkin_busy' => 'Busy',
    'checkin_packed' => 'Packed',
    'checkin_parking' => 'Parking availability?',
    'checkin_plenty' => 'Plenty',
    'checkin_available' => 'Available',
    'checkin_limited' => 'Limited',
    'checkin_full' => 'Full',
    'checkin_water' => 'Water conditions?',
    'checkin_calm' => 'Calm',
    'checkin_small_waves' => 'Small',
    'checkin_choppy' => 'Choppy',
    'checkin_rough' => 'Rough',
    'checkin_sargassum' => 'Sargassum level?',
    'checkin_none' => 'None',
    'checkin_heavy' => 'Heavy',
    'checkin_notes' => 'Any other notes?',
    'checkin_notes_placeholder' => 'Share a quick tip for others...',
    'checkin_submit' => 'Submit Check-In',
    'checkin_cancel' => 'Cancel',
    'checkin_select_condition' => 'Please select at least one condition or add a note.',
    'checkin_submitting' => 'Submitting...',
    'checkin_success' => 'Thanks for checking in!',
    'checkin_toast_success' => 'Check-in submitted!',
    'checkin_error' => 'Failed to submit check-in',
    'checkin_network_error' => 'Network error. Please try again.',
    // Write Review Modal
    'review_modal_title' => 'Write a Review',
    'review_share_experience' => 'Share your experience at :name',
    'review_your_rating' => 'Your Rating',
    'review_title_label' => 'Review Title',
    'review_title_placeholder' => 'Sum up your experience in a few words...',
    'review_your_review' => 'Your Review',
    'review_body_placeholder' => 'What did you like or dislike? Share tips for other visitors...',
    'review_min_chars' => 'Minimum 20 characters',
    'review_when_visit' => 'When did you visit?',
    'review_who_with' => 'Who did you go with?',
    'review_select' => 'Select...',
    'review_solo' => 'Solo',
    'review_partner' => 'Partner/Couple',
    'review_family' => 'Family',
    'review_friends' => 'Friends',
    'review_group' => 'Group',
    'review_add_photos' => 'Add Photos',
    'review_click_upload' => 'Click to upload photos',
    'review_file_types' => 'JPG, PNG, or WebP (max 10MB each)',
    'review_submit' => 'Submit Review',
    'review_cancel' => 'Cancel',
    // Upload Photos Modal
    'upload_modal_title' => 'Upload Photos',
    'upload_share_photos' => 'Share your photos of :name',
    'upload_click_select' => 'Click to select a photo',
    'upload_file_types' => 'JPG, PNG, or WebP (max 10MB)',
    // Misc beach page
    'tags_more' => '+:count more',
    'add_photo' => 'Add',
    'essential_tips' => 'Essential tips & information',
    'optional' => '(optional)',
    'saved_toast' => 'Saved!',
    'removed_toast' => 'Removed from favorites',
    'favorite_error' => 'Could not update favorite.',
    'photo_label' => 'Photo',
];

$beachKeysEs = [
    // Report Modal
    'report_modal_title' => 'Reportar Informacion Desactualizada',
    'report_help_text' => 'Ayudanos a mantener la informacion de la playa precisa. Cuentanos que ha cambiado en :name.',
    'report_what_updating' => 'Que necesita actualizarse?',
    'report_opt_conditions' => 'Condiciones de playa (sargazo, oleaje, viento)',
    'report_opt_amenities' => 'Servicios (estacionamiento, banos, etc.)',
    'report_opt_access' => 'Acceso o direcciones',
    'report_opt_safety' => 'Informacion de seguridad',
    'report_opt_other' => 'Otro',
    'report_details' => 'Detalles',
    'report_placeholder' => 'Cuentanos que es diferente a lo que se muestra...',
    'report_submit' => 'Enviar Reporte',
    'report_cancel' => 'Cancelar',
    'report_select_issue' => 'Por favor selecciona al menos un problema a reportar.',
    'report_submitting' => 'Enviando...',
    'report_success' => 'Gracias! Tu reporte ha sido enviado y sera revisado pronto.',
    'report_toast_success' => 'Reporte enviado. Gracias!',
    // Check-In Modal
    'checkin_modal_title' => 'Registro',
    'checkin_share_text' => 'Comparte lo que ves en :name ahora mismo!',
    'checkin_crowded' => 'Que tan lleno esta?',
    'checkin_empty' => 'Vacio',
    'checkin_light' => 'Poco',
    'checkin_moderate' => 'Moderado',
    'checkin_busy' => 'Lleno',
    'checkin_packed' => 'Abarrotado',
    'checkin_parking' => 'Disponibilidad de estacionamiento?',
    'checkin_plenty' => 'Abundante',
    'checkin_available' => 'Disponible',
    'checkin_limited' => 'Limitado',
    'checkin_full' => 'Lleno',
    'checkin_water' => 'Condiciones del agua?',
    'checkin_calm' => 'Calma',
    'checkin_small_waves' => 'Pequeno',
    'checkin_choppy' => 'Picado',
    'checkin_rough' => 'Fuerte',
    'checkin_sargassum' => 'Nivel de sargazo?',
    'checkin_none' => 'Ninguno',
    'checkin_heavy' => 'Abundante',
    'checkin_notes' => 'Alguna otra nota?',
    'checkin_notes_placeholder' => 'Comparte un consejo rapido para otros...',
    'checkin_submit' => 'Enviar Registro',
    'checkin_cancel' => 'Cancelar',
    'checkin_select_condition' => 'Por favor selecciona al menos una condicion o agrega una nota.',
    'checkin_submitting' => 'Enviando...',
    'checkin_success' => 'Gracias por tu registro!',
    'checkin_toast_success' => 'Registro enviado!',
    'checkin_error' => 'Error al enviar el registro',
    'checkin_network_error' => 'Error de red. Por favor intenta de nuevo.',
    // Write Review Modal
    'review_modal_title' => 'Escribir una Resena',
    'review_share_experience' => 'Comparte tu experiencia en :name',
    'review_your_rating' => 'Tu Calificacion',
    'review_title_label' => 'Titulo de la Resena',
    'review_title_placeholder' => 'Resume tu experiencia en pocas palabras...',
    'review_your_review' => 'Tu Resena',
    'review_body_placeholder' => 'Que te gusto o no? Comparte consejos para otros visitantes...',
    'review_min_chars' => 'Minimo 20 caracteres',
    'review_when_visit' => 'Cuando visitaste?',
    'review_who_with' => 'Con quien fuiste?',
    'review_select' => 'Seleccionar...',
    'review_solo' => 'Solo',
    'review_partner' => 'Pareja',
    'review_family' => 'Familia',
    'review_friends' => 'Amigos',
    'review_group' => 'Grupo',
    'review_add_photos' => 'Agregar Fotos',
    'review_click_upload' => 'Haz clic para subir fotos',
    'review_file_types' => 'JPG, PNG o WebP (max 10MB cada una)',
    'review_submit' => 'Enviar Resena',
    'review_cancel' => 'Cancelar',
    // Upload Photos Modal
    'upload_modal_title' => 'Subir Fotos',
    'upload_share_photos' => 'Comparte tus fotos de :name',
    'upload_click_select' => 'Haz clic para seleccionar una foto',
    'upload_file_types' => 'JPG, PNG o WebP (max 10MB)',
    // Misc beach page
    'tags_more' => '+:count mas',
    'add_photo' => 'Agregar',
    'essential_tips' => 'Consejos e informacion esencial',
    'optional' => '(opcional)',
    'saved_toast' => 'Guardado!',
    'removed_toast' => 'Eliminado de favoritos',
    'favorite_error' => 'No se pudo actualizar favorito.',
    'photo_label' => 'Foto',
];

// Merge into beach section
$en['beach'] = array_merge($en['beach'], $beachKeysEn);
$es['beach'] = array_merge($es['beach'], $beachKeysEs);

// =====================================================================
// MUNICIPALITY SECTION ADDITIONS
// =====================================================================
$en['pages']['municipality']['faq_best_a'] = ':beach is one of the top-rated beaches in :municipality with a :rating star rating.';
$en['pages']['municipality']['faq_best_a_fallback'] = ':municipality has many beautiful beaches to choose from.';
$en['pages']['municipality']['faq_activities_a'] = 'Beaches in :municipality offer :tags. Each beach has unique characteristics - some are perfect for families, others for surfing or snorkeling.';
$en['pages']['municipality']['faq_activities_fallback'] = 'various activities';
$en['pages']['municipality']['and_more'] = '& more';
$en['pages']['municipality']['intro_popular'] = 'Popular beaches include :beaches. Each beach features detailed information including GPS coordinates, amenities, current conditions, and visitor reviews to help you plan the perfect beach day.';

$es['pages']['municipality']['faq_best_a'] = ':beach es una de las playas mejor calificadas en :municipality con una calificacion de :rating estrellas.';
$es['pages']['municipality']['faq_best_a_fallback'] = ':municipality tiene muchas playas hermosas para elegir.';
$es['pages']['municipality']['faq_activities_a'] = 'Las playas en :municipality ofrecen :tags. Cada playa tiene caracteristicas unicas - algunas son perfectas para familias, otras para surfing o snorkel.';
$es['pages']['municipality']['faq_activities_fallback'] = 'varias actividades';
$es['pages']['municipality']['and_more'] = 'y mas';
$es['pages']['municipality']['intro_popular'] = 'Las playas populares incluyen :beaches. Cada playa presenta informacion detallada incluyendo coordenadas GPS, servicios, condiciones actuales y resenas de visitantes para ayudarte a planificar el dia de playa perfecto.';

// =====================================================================
// JS SECTION ADDITIONS
// =====================================================================
$en['js']['close_notification'] = 'Close notification';
$en['js']['clear_search'] = 'Clear search';
$en['js']['share_label'] = 'Share';
$en['js']['share_brand'] = 'Puerto Rico Beach Finder';
$en['js']['map_away'] = 'away';
$en['js']['fav_remove_aria'] = 'Remove from favorites';
$en['js']['fav_add_aria'] = 'Add to favorites';

$es['js']['close_notification'] = 'Cerrar notificacion';
$es['js']['clear_search'] = 'Limpiar busqueda';
$es['js']['share_label'] = 'Compartir';
$es['js']['share_brand'] = 'Puerto Rico Beach Finder';
$es['js']['map_away'] = 'de distancia';
$es['js']['fav_remove_aria'] = 'Eliminar de favoritos';
$es['js']['fav_add_aria'] = 'Agregar a favoritos';

// =====================================================================
// TIME SECTION - Add 'never'
// =====================================================================
$en['time']['never'] = 'Never';
$es['time']['never'] = 'Nunca';

// =====================================================================
// NEW SECTIONS
// =====================================================================

// Badges
$en['badges'] = [
    'top_rated' => 'Top Rated',
    'family_pick' => 'Family Pick',
    'hidden_gem' => 'Hidden Gem',
    'surfers_fave' => "Surfer's Fave",
    'insta_worthy' => 'Insta-Worthy',
    'local_secret' => 'Local Secret',
];
$es['badges'] = [
    'top_rated' => 'Mejor Calificada',
    'family_pick' => 'Ideal para Familias',
    'hidden_gem' => 'Joya Escondida',
    'surfers_fave' => 'Favorita de Surfistas',
    'insta_worthy' => 'Digna de Instagram',
    'local_secret' => 'Secreto Local',
];

// Explorer levels
$en['explorer'] = [
    'newcomer' => 'Newcomer',
    'explorer' => 'Explorer',
    'guide' => 'Guide',
    'expert' => 'Expert',
    'legend' => 'Legend',
    'highest_level' => 'You\'ve reached the highest level!',
    'beaches_to_next' => ':count more beach to :level|:count more beaches to :level',
];
$es['explorer'] = [
    'newcomer' => 'Principiante',
    'explorer' => 'Explorador',
    'guide' => 'Guia',
    'expert' => 'Experto',
    'legend' => 'Leyenda',
    'highest_level' => 'Has alcanzado el nivel mas alto!',
    'beaches_to_next' => ':count playa mas para :level|:count playas mas para :level',
];

// Best For summary labels
$en['best_for_labels'] = [
    'families' => 'Families',
    'surfing' => 'Surfing',
    'snorkeling' => 'Snorkeling',
    'diving' => 'Diving',
    'swimming' => 'Swimming',
    'relaxing' => 'Relaxing',
    'photography' => 'Photography',
    'quiet_escape' => 'Quiet Escape',
    'fishing' => 'Fishing',
    'camping' => 'Camping',
];
$es['best_for_labels'] = [
    'families' => 'Familias',
    'surfing' => 'Surfing',
    'snorkeling' => 'Snorkel',
    'diving' => 'Buceo',
    'swimming' => 'Natacion',
    'relaxing' => 'Relajacion',
    'photography' => 'Fotografia',
    'quiet_escape' => 'Escape Tranquilo',
    'fishing' => 'Pesca',
    'camping' => 'Camping',
];

// Guide map panel
$en['guide_map'] = [
    'map_view' => 'Map View',
    'map_desc' => 'Explore these beaches on the map.',
    'view_map' => 'View Map',
    'empty_notice' => 'No mappable beaches are available for this guide right now.',
    'map_unavailable' => 'Map unavailable',
    'list_view' => 'List View',
    'loading_map' => 'Loading map...',
    'map_error' => 'Unable to load map right now.',
];
$es['guide_map'] = [
    'map_view' => 'Vista de Mapa',
    'map_desc' => 'Explora estas playas en el mapa.',
    'view_map' => 'Ver Mapa',
    'empty_notice' => 'No hay playas mapeables disponibles para esta guia ahora.',
    'map_unavailable' => 'Mapa no disponible',
    'list_view' => 'Vista de Lista',
    'loading_map' => 'Cargando mapa...',
    'map_error' => 'No se pudo cargar el mapa ahora.',
];

// Parking descriptions
$en['parking_desc'] = [
    'easy' => 'Plenty of parking available, rarely fills up',
    'moderate' => 'Usually find parking, may fill on weekends',
    'difficult' => 'Limited spots, arrive early on busy days',
    'very_difficult' => 'Very limited parking, consider alternate transport',
];
$es['parking_desc'] = [
    'easy' => 'Amplio estacionamiento disponible, rara vez se llena',
    'moderate' => 'Usualmente hay estacionamiento, puede llenarse los fines de semana',
    'difficult' => 'Espacios limitados, llega temprano en dias concurridos',
    'very_difficult' => 'Estacionamiento muy limitado, considera transporte alternativo',
];

// Guides index page
$en['guides_index'] = [
    'page_title' => 'Puerto Rico Beach Guides',
    'page_description' => 'Expert guides to help you plan the perfect Puerto Rico beach vacation. Tips on transportation, safety, best times to visit, and more.',
    'breadcrumb_home' => 'Home',
    'breadcrumb_guides' => 'Guides',
    'read_guide' => 'Read guide',
    'cta_title' => 'Ready to Explore?',
    'cta_desc' => 'Browse our collection of 230+ beaches across Puerto Rico and find your perfect beach destination.',
    'cta_button' => 'Browse All Beaches',
    'guide_transport_title' => 'Getting to Puerto Rico Beaches',
    'guide_transport_desc' => 'Complete transportation guide including car rentals, public transit, ride-shares, and insider tips for reaching beaches across the island.',
    'guide_transport_time' => '12 min read',
    'guide_safety_title' => 'Beach Safety Tips',
    'guide_safety_desc' => 'Essential safety information covering rip currents, jellyfish, sun protection, and emergency procedures for Puerto Rico beaches.',
    'guide_safety_time' => '10 min read',
    'guide_besttime_title' => 'Best Time to Visit',
    'guide_besttime_desc' => 'Month-by-month breakdown of weather patterns, hurricane season, peak tourist times, and the ideal windows for your beach trip.',
    'guide_besttime_time' => '11 min read',
    'guide_packing_title' => 'Beach Packing List',
    'guide_packing_desc' => 'Comprehensive checklist of everything you need for a perfect Puerto Rico beach day, from reef-safe sunscreen to snorkeling gear.',
    'guide_packing_time' => '8 min read',
    'guide_islands_title' => 'Culebra vs Vieques',
    'guide_islands_desc' => 'Side-by-side comparison of Puerto Rico\'s two island paradise destinations - beaches, transportation, accommodations, and unique experiences.',
    'guide_islands_time' => '13 min read',
    'guide_bio_title' => 'Bioluminescent Bays Guide',
    'guide_bio_desc' => 'Everything you need to know about Puerto Rico\'s magical glowing waters - best times, tours, and how to experience bioluminescence.',
    'guide_bio_time' => '10 min read',
    'guide_snorkeling_title' => 'Snorkeling Guide',
    'guide_snorkeling_desc' => 'Top snorkeling spots, equipment tips, marine life guide, and safety advice for exploring Puerto Rico\'s underwater world.',
    'guide_snorkeling_time' => '14 min read',
    'guide_surfing_title' => 'Surfing Guide',
    'guide_surfing_desc' => 'Best surf breaks, seasonal patterns, board rentals, and local surf culture across Puerto Rico\'s coastline.',
    'guide_surfing_time' => '12 min read',
    'guide_photo_title' => 'Beach Photography Tips',
    'guide_photo_desc' => 'Capture stunning beach photos with tips on golden hour timing, composition, waterproof gear, and the most photogenic beaches.',
    'guide_photo_time' => '9 min read',
    'guide_family_title' => 'Family Beach Vacation Planning',
    'guide_family_desc' => 'Plan the perfect family beach trip with kid-friendly beaches, safety tips, packing lists, and age-appropriate activity recommendations.',
    'guide_family_time' => '15 min read',
    'guide_springbreak_title' => 'Spring Break Beaches Guide',
    'guide_springbreak_desc' => 'Best beaches for spring break in Puerto Rico - party spots, chill vibes, group activities, and budget-friendly options.',
    'guide_springbreak_time' => '11 min read',
];
$es['guides_index'] = [
    'page_title' => 'Guias de Playas de Puerto Rico',
    'page_description' => 'Guias expertas para ayudarte a planificar las vacaciones de playa perfectas en Puerto Rico. Consejos de transporte, seguridad, mejores epocas para visitar y mas.',
    'breadcrumb_home' => 'Inicio',
    'breadcrumb_guides' => 'Guias',
    'read_guide' => 'Leer guia',
    'cta_title' => 'Listo para Explorar?',
    'cta_desc' => 'Explora nuestra coleccion de mas de 230 playas en Puerto Rico y encuentra tu destino de playa perfecto.',
    'cta_button' => 'Ver Todas las Playas',
    'guide_transport_title' => 'Como Llegar a las Playas de Puerto Rico',
    'guide_transport_desc' => 'Guia completa de transporte incluyendo alquiler de autos, transporte publico, viajes compartidos y consejos para llegar a las playas.',
    'guide_transport_time' => '12 min de lectura',
    'guide_safety_title' => 'Consejos de Seguridad en la Playa',
    'guide_safety_desc' => 'Informacion esencial de seguridad sobre corrientes, medusas, proteccion solar y procedimientos de emergencia en playas de Puerto Rico.',
    'guide_safety_time' => '10 min de lectura',
    'guide_besttime_title' => 'Mejor Epoca para Visitar',
    'guide_besttime_desc' => 'Desglose mes a mes de patrones climaticos, temporada de huracanes, picos turisticos y las mejores ventanas para tu viaje de playa.',
    'guide_besttime_time' => '11 min de lectura',
    'guide_packing_title' => 'Lista de Empaque para la Playa',
    'guide_packing_desc' => 'Lista completa de todo lo que necesitas para un dia perfecto de playa en Puerto Rico, desde bloqueador reef-safe hasta equipo de snorkel.',
    'guide_packing_time' => '8 min de lectura',
    'guide_islands_title' => 'Culebra vs Vieques',
    'guide_islands_desc' => 'Comparacion lado a lado de los dos destinos paradisiacos de Puerto Rico - playas, transporte, alojamiento y experiencias unicas.',
    'guide_islands_time' => '13 min de lectura',
    'guide_bio_title' => 'Guia de Bahias Bioluminiscentes',
    'guide_bio_desc' => 'Todo lo que necesitas saber sobre las magicas aguas brillantes de Puerto Rico - mejores horarios, tours y como experimentar la bioluminiscencia.',
    'guide_bio_time' => '10 min de lectura',
    'guide_snorkeling_title' => 'Guia de Snorkel',
    'guide_snorkeling_desc' => 'Mejores spots de snorkel, consejos de equipo, guia de vida marina y consejos de seguridad para explorar el mundo submarino de Puerto Rico.',
    'guide_snorkeling_time' => '14 min de lectura',
    'guide_surfing_title' => 'Guia de Surfing',
    'guide_surfing_desc' => 'Mejores olas, patrones estacionales, alquiler de tablas y cultura del surf en toda la costa de Puerto Rico.',
    'guide_surfing_time' => '12 min de lectura',
    'guide_photo_title' => 'Consejos de Fotografia de Playa',
    'guide_photo_desc' => 'Captura fotos increibles de playa con consejos sobre hora dorada, composicion, equipo impermeable y las playas mas fotogenicas.',
    'guide_photo_time' => '9 min de lectura',
    'guide_family_title' => 'Planificacion de Vacaciones Familiares en la Playa',
    'guide_family_desc' => 'Planifica el viaje familiar perfecto con playas para ninos, consejos de seguridad, listas de empaque y actividades por edad.',
    'guide_family_time' => '15 min de lectura',
    'guide_springbreak_title' => 'Guia de Playas para Spring Break',
    'guide_springbreak_desc' => 'Mejores playas para spring break en Puerto Rico - lugares de fiesta, ambientes relajados, actividades grupales y opciones economicas.',
    'guide_springbreak_time' => '11 min de lectura',
];

// Airport page transport labels
$en['airport_labels'] = [
    'best_for' => 'Best for:',
    'cost' => 'Cost:',
    'uber_lyft' => 'Uber/Lyft:',
    'airport_taxis' => 'Airport taxis:',
    'pro_tip' => 'Pro tip:',
    'pros' => 'Pros:',
    'cons' => 'Cons:',
    'route' => 'Route:',
];
$es['airport_labels'] = [
    'best_for' => 'Ideal para:',
    'cost' => 'Costo:',
    'uber_lyft' => 'Uber/Lyft:',
    'airport_taxis' => 'Taxis del aeropuerto:',
    'pro_tip' => 'Consejo:',
    'pros' => 'Ventajas:',
    'cons' => 'Desventajas:',
    'route' => 'Ruta:',
];

// Weather widget labels
$en['weather_ui'] = [
    'beach_score' => 'Beach Score',
    'uv_label' => 'UV',
    'wind_label' => 'wind',
    'current_label' => 'Current',
    'mph' => 'mph',
    'day_mon' => 'Mon',
    'day_tue' => 'Tue',
    'day_wed' => 'Wed',
    'day_thu' => 'Thu',
    'day_fri' => 'Fri',
    'day_sat' => 'Sat',
    'day_sun' => 'Sun',
];
$es['weather_ui'] = [
    'beach_score' => 'Puntuacion de Playa',
    'uv_label' => 'UV',
    'wind_label' => 'viento',
    'current_label' => 'Actual',
    'mph' => 'mph',
    'day_mon' => 'Lun',
    'day_tue' => 'Mar',
    'day_wed' => 'Mie',
    'day_thu' => 'Jue',
    'day_fri' => 'Vie',
    'day_sat' => 'Sab',
    'day_sun' => 'Dom',
];

// Aria-labels (shared across components)
$en['aria'] = [
    'breadcrumb' => 'Breadcrumb',
    'beach_filters' => 'Beach filters',
    'close_filters' => 'Close filters',
    'view_mode' => 'View mode',
    'filter_municipality' => 'Filter by municipality',
    'sort_by' => 'Sort beaches by',
    'filter_type' => 'Filter by beach type',
    'clear_all_filters' => 'Clear all filters',
    'applied_filters' => 'Applied filters',
    'enable_location' => 'Enable location to see distances',
    'list_view' => 'List',
    'map_view' => 'Map',
    'switch_view' => 'Switch collection view',
    'page_content' => 'Page content',
    'beach_explorer' => 'Beach explorer controls',
    'share_beach' => 'Share this beach',
    'get_directions' => 'Get directions',
    'close_share' => 'Close share dialog',
    'image_gallery' => 'Image gallery',
    'close_gallery' => 'Close gallery',
    'prev_image' => 'Previous image',
    'next_image' => 'Next image',
    'close' => 'Close',
    'close_review' => 'Close review form',
    'comparison' => 'Beach comparison selection',
    'clear_comparison' => 'Clear comparison selection',
];
$es['aria'] = [
    'breadcrumb' => 'Ruta de navegacion',
    'beach_filters' => 'Filtros de playa',
    'close_filters' => 'Cerrar filtros',
    'view_mode' => 'Modo de vista',
    'filter_municipality' => 'Filtrar por municipio',
    'sort_by' => 'Ordenar playas por',
    'filter_type' => 'Filtrar por tipo de playa',
    'clear_all_filters' => 'Limpiar todos los filtros',
    'applied_filters' => 'Filtros aplicados',
    'enable_location' => 'Activar ubicacion para ver distancias',
    'list_view' => 'Lista',
    'map_view' => 'Mapa',
    'switch_view' => 'Cambiar vista de coleccion',
    'page_content' => 'Contenido de la pagina',
    'beach_explorer' => 'Controles del explorador de playas',
    'share_beach' => 'Compartir esta playa',
    'get_directions' => 'Obtener direcciones',
    'close_share' => 'Cerrar dialogo de compartir',
    'image_gallery' => 'Galeria de imagenes',
    'close_gallery' => 'Cerrar galeria',
    'prev_image' => 'Imagen anterior',
    'next_image' => 'Imagen siguiente',
    'close' => 'Cerrar',
    'close_review' => 'Cerrar formulario de resena',
    'comparison' => 'Seleccion de comparacion de playas',
    'clear_comparison' => 'Limpiar seleccion de comparacion',
];

// Related guides (used in helpers.php)
$en['related_guides'] = [
    'surfing' => 'Puerto Rico Surfing Guide',
    'snorkeling' => 'Snorkeling in Puerto Rico',
    'family' => 'Family Beach Vacation Planning',
    'photography' => 'Beach Photography Tips',
    'transport' => 'Getting to Puerto Rico Beaches',
    'safety' => 'Beach Safety Tips',
    'packing' => 'Beach Packing List',
    'best_time' => 'Best Time to Visit Puerto Rico Beaches',
];
$es['related_guides'] = [
    'surfing' => 'Guia de Surfing en Puerto Rico',
    'snorkeling' => 'Snorkel en Puerto Rico',
    'family' => 'Planificacion de Vacaciones Familiares en la Playa',
    'photography' => 'Consejos de Fotografia de Playa',
    'transport' => 'Como Llegar a las Playas de Puerto Rico',
    'safety' => 'Consejos de Seguridad en la Playa',
    'packing' => 'Lista de Empaque para la Playa',
    'best_time' => 'Mejor Epoca para Visitar las Playas de Puerto Rico',
];

// =====================================================================
// WRITE FILES
// =====================================================================
function exportArray(array $arr, int $indent = 1): string {
    $pad = str_repeat('    ', $indent);
    $lines = [];
    foreach ($arr as $key => $value) {
        $escapedKey = str_replace("'", "\\'", $key);
        if (is_array($value)) {
            $lines[] = $pad . "'" . $escapedKey . "' => [";
            $lines[] = exportArray($value, $indent + 1);
            $lines[] = $pad . "],";
        } else {
            $escapedValue = str_replace("'", "\\'", $value);
            $lines[] = $pad . "'" . $escapedKey . "' => '" . $escapedValue . "',";
        }
    }
    return implode("\n", $lines);
}

function writePhpArray(string $filePath, array $data, string $comment): void {
    $output = "<?php\n/**\n * {$comment}\n */\n\nreturn [\n";
    $output .= exportArray($data);
    $output .= "\n];\n";
    file_put_contents($filePath, $output);
}

writePhpArray($enFile, $en, 'English Translations');
writePhpArray($esFile, $es, 'Spanish Translations');

// Count keys
function countKeys(array $arr): int {
    $count = 0;
    foreach ($arr as $value) {
        if (is_array($value)) {
            $count += countKeys($value);
        } else {
            $count++;
        }
    }
    return $count;
}

$enCount = countKeys($en);
$esCount = countKeys($es);
echo "Done! en.php: {$enCount} keys, es.php: {$esCount} keys\n";
if ($enCount !== $esCount) {
    echo "WARNING: Key count mismatch!\n";
} else {
    echo "Key counts match.\n";
}
