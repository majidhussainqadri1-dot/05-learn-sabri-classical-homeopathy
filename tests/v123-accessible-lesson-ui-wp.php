<?php
/** Real disposable WP markup checks; not a WCAG certification or Live acceptance. */
if(!defined('ABSPATH')){fwrite(STDERR,"WordPress missing\n");exit(1);}
function f05_ui_assert($ok,$why){$GLOBALS['f05_ui_n']++;if(!$ok){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
$GLOBALS['f05_ui_n']=0;
$frontend=new LSCH_Frontend();
$course=wp_insert_post(array('post_type'=>'lsch_course','post_status'=>'draft',
    'post_title'=>'Accessible Example Course','post_excerpt'=>'Public display prototype'),true);
f05_ui_assert(!is_wp_error($course)&&$course>0,'Disposable course fixture created');
$post=get_post($course);
$cardMethod=new ReflectionMethod('LSCH_Frontend','card');
$card=$cardMethod->invoke($frontend,$post);
f05_ui_assert(is_string($card)&&strpos($card,'class="lsch-card-media"')!==false,'Media card rendered');
f05_ui_assert(strpos($card,'aria-label="Open Accessible Example Course"')!==false,
    'Image/icon-only media card has an understandable accessible link name');
$lesson=wp_insert_post(array('post_type'=>'lsch_lesson','post_status'=>'draft',
    'post_title'=>'Progress Status Test Lesson'),true);
f05_ui_assert(!is_wp_error($lesson)&&$lesson>0,'Disposable lesson fixture created');
$lessonMethod=new ReflectionMethod('LSCH_Frontend','lesson_content');
$markup=$lessonMethod->invoke($frontend,$lesson,'<p>Educational fixture</p>','');
f05_ui_assert(preg_match('/class="lsch-learning-tools".*?data-lsch-progress="'.(int)$lesson.'".*?data-lsch-status role="status" aria-live="polite"/s',$markup)===1,
    'Progress and bookmark controls have nearby live status in their own group');
f05_ui_assert(substr_count($markup,'data-lsch-status')===2,
    'Progress tools and private note retain separate status projections');
f05_ui_assert(strpos($markup,'data-lsch-note="'.(int)$lesson.'"')!==false,
    'Private-note field remains tied to current lesson');
wp_delete_post($course,true);wp_delete_post($lesson,true);
echo 'PASS: File05 accessible card links and visible progress/private status, '.$GLOBALS['f05_ui_n']." disposable WordPress assertions.\n";
