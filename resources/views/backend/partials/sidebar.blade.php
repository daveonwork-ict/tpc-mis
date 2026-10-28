<aside class="sidebar" id="sidebar">

    <x-sidebar-header />

    <div class="sidebar-menu srollbar">
        <div class="sidebar-menu-section">
            <!-- parent menu list start  -->
            <ul class="sidebar-dropdown-menu">
                <li class="sidebar-menu-item">
                    <a href="{{ route('dashboard') }}" class="parent-item-content">
                        {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="Dashboard" /> --}}
                        <i class="las la-tachometer-alt"></i>
                        <span class="on-half-expanded">{{ ___('dashboard.Dashboard') }} </span>
                    </a>
                </li>

                @if (hasModule('MultiBranch'))
                    @include('multibranch::layouts.menu')
                @endif

               

                

                @if (hasModule('ActivityLog'))
                    @include('activitylog::partials.sidebar.admin_menu')
                @endif

                <!-- Admission start -->
                @if (hasPermission('application_read') || hasPermission('inquiry_read') || hasPermission('admission_setup_read'))
                    <li class="sidebar-menu-item {{ set_menu(['admissions*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-user-check"></i>
                            <span class="on-half-expanded">{{ ___('admissions.admissions') }}</span>
                        </a>
                        <ul class="child-menu-list">
                            @if (hasPermission('application_read'))
                                <li class="sidebar-menu-item {{ set_menu(['admissions/applications*']) }}">
                                    <a href="{{ route('admissions.applications.index') }}">{{ ___('admissions.applications') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('inquiry_read'))
                                <li class="sidebar-menu-item {{ set_menu(['admissions/inquiries*']) }}">
                                    <a href="{{ route('admissions.inquiries.index') }}">{{ ___('admissions.inquiries') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('admission_setup_read'))
                                <li class="sidebar-menu-item {{ set_menu(['admissions/setup*']) }}">
                                    <a href="{{ route('admissions.setup.programs') }}">{{ ___('admissions.admission_setup') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                <!-- Admission end -->

                <!-- Enrollment start -->
                @if (hasPermission('enrollment_read') || hasPermission('offering_read') || hasPermission('enrollment_setup_read') || hasPermission('class_read'))
                    <li class="sidebar-menu-item {{ set_menu(['enrollment*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-clipboard-list"></i>
                            <span class="on-half-expanded">{{ ___('enrollment.enrollment') }}</span>
                        </a>
                        <ul class="child-menu-list">
                            @if (hasPermission('class_read'))
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/classes*']) }}">
                                    <a href="{{ route('enrollment.classes.index') }}">{{ ___('enrollment.my_classes') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('enrollment_read'))
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/enrollments*', 'enrollment/students*']) }}">
                                    <a href="{{ route('enrollment.enrollments.index') }}">{{ ___('enrollment.enrollments') }}</a>
                                </li>
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/reports*']) }}">
                                    <a href="{{ route('enrollment.reports.index') }}">{{ ___('enrollment.reports') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('offering_read'))
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/offerings*']) }}">
                                    <a href="{{ route('enrollment.offerings.index') }}">{{ ___('enrollment.course_offerings') }}</a>
                                </li>
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/batches*']) }}">
                                    <a href="{{ route('enrollment.batches.index') }}">{{ ___('enrollment.training_batches') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('enrollment_setup_read'))
                                <li class="sidebar-menu-item {{ set_menu(['enrollment/setup*']) }}">
                                    <a href="{{ route('enrollment.setup.curricula') }}">{{ ___('enrollment.enrollment_setup') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                <!-- Enrollment end -->


                {{-- Student info --}}
                @if (
                    (hasPermission('student_read') ||
                        hasPermission('disabled_students_read') ||
                        hasPermission('admission_read') ||
                        hasPermission('parent_read')) &&
                        hasFeature('student_info'))
                    <li class="sidebar-menu-item {{ set_menu(['students*', 'student*', 'student/category*']) }}">
                        <a class="parent-item-content has-arrow">
                            {{-- <img src="{{ asset('backend') }}/assets/images/icons/clipboard.svg" alt="Dashboard" /> --}}
                            <i class="las la-users"></i>
                            <span class="on-half-expanded">{{ ___('settings.student_info') }}</span>
                        </a>

                        <!-- second layer child menu list start  -->

                        <ul class="child-menu-list">
                            @if (hasPermission('student_read'))
                                <li
                                    class="sidebar-menu-item {{ set_menu(['student', 'student/create', 'student/edit']) }}">
                                    <a href="{{ route('student.index') }}">{{ ___('settings.stundets') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('disabled_students_read'))
                                <li class="sidebar-menu-item {{ set_menu(['disabled/students*']) }}">
                                    <a
                                        href="{{ route('disabled_students.index') }}">{{ ___('student_info.disabled_students') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('parent_read'))
                                <li class="sidebar-menu-item {{ set_menu(['parent*']) }}">
                                    <a href="{{ route('parent.index') }}">{{ ___('student_info.guardian') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('student_read'))
                                <li class="sidebar-menu-item {{ set_menu(['students*']) }}">
                                    <a href="{{ route('student.list') }}">{{ ___('student_info.student') }}
                                        {{ ___('common.export') }}</a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif



                {{-- Start Academic --}}
                @if (
                    (hasPermission('classes_read') ||
                        hasPermission('section_read') ||
                        hasPermission('shift_read') ||
                        hasPermission('class_setup_read') ||
                        hasPermission('subject_read') ||
                        hasPermission('subject_assign_read') ||
                        hasPermission('time_schedule_read') ||
                        hasPermission('class_room_read')) &&
                        hasFeature('academic'))
                    <li class="sidebar-menu-item {{ set_menu(['academic*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-book-open"></i>
                            <span class="on-half-expanded">{{ ___('settings.academic') }}</span>
                        </a>
                        <ul class="child-menu-list">
                            @if (hasPermission('classes_read'))
                                <li class="sidebar-menu-item {{ set_menu(['classes*']) }}">
                                    <a href="{{ route('classes.index') }}">{{ ___('settings.class') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('section_read'))
                                <li class="sidebar-menu-item {{ set_menu(['section*']) }}">
                                    <a href="{{ route('section.index') }}">{{ ___('settings.section') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('shift_read'))
                                <li class="sidebar-menu-item {{ set_menu(['shift*']) }}">
                                    <a href="{{ route('shift.index') }}">{{ ___('settings.shift') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('class_setup_read'))
                                <li class="sidebar-menu-item {{ set_menu(['class-setup*']) }}">
                                    <a href="{{ route('class-setup.index') }}">{{ ___('settings.class_setup') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('subject_read'))
                                <li class="sidebar-menu-item {{ set_menu(['subject*']) }}">
                                    <a href="{{ route('subject.index') }}">{{ ___('settings.subject') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('subject_assign_read'))
                                <li class="sidebar-menu-item {{ set_menu(['assign-subject*']) }}">
                                    <a
                                        href="{{ route('assign-subject.index') }}">{{ ___('settings.subject_assign') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('time_schedule_read'))
                                <li class="sidebar-menu-item {{ set_menu(['time/schedule*']) }}">
                                    <a
                                        href="{{ route('time_schedule.index') }}">{{ ___('academic.time_schedule') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('class_room_read'))
                                <li class="sidebar-menu-item {{ set_menu(['class-room*']) }}">
                                    <a href="{{ route('class-room.index') }}">{{ ___('settings.class_room') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                {{-- End Academic --}}

                 @if (hasModule('BehaviourRecord'))
                    @include('behaviourrecord::layouts.admin_sidebar_menu')
                @endif

                <!-- Online Examination start -->
                @if (
                    (hasPermission('question_group_read') ||
                        hasPermission('question_bank_read') ||
                        hasPermission('online_exam_read') ||
                        hasPermission('online_exam_type_read')) &&
                        hasFeature('online_examination'))
                    <li
                        class="sidebar-menu-item {{ set_menu(['question-group*', 'question-bank*', 'online-exam*', 'online-exam-type*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-swatchbook"></i>
                            <span class="on-half-expanded">{{ ___('online-examination.online_examination') }}</span>
                        </a>

                        <!-- second layer child menu list start  -->

                        <ul class="child-menu-list">
                            <li class="sidebar-menu-item {{ set_menu(['online-exam-type*']) }}">
                                <a href="{{ route('online-exam-type.index') }}">{{ ___('settings.type') }}</a>
                            </li>
                            <li class="sidebar-menu-item {{ set_menu(['question-group*']) }}">
                                <a
                                    href="{{ route('question-group.index') }}">{{ ___('online-examination.question_group') }}</a>
                            </li>
                            <li class="sidebar-menu-item {{ set_menu(['question-bank*']) }}">
                                <a
                                    href="{{ route('question-bank.index') }}">{{ ___('online-examination.question_bank') }}</a>
                            </li>
                            <li
                                class="sidebar-menu-item {{ set_menu(['online-exam', 'online-exam/create', 'online-exam/edit*']) }}">
                                <a
                                    href="{{ route('online-exam.index') }}">{{ ___('online-examination.online_exam') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif
                <!-- Online Examination end -->
                @if (hasModule('StudyMaterial'))
                    @include('studymaterial::admin_sidebar_menu')
                @endif

                @if (hasPermission('notice_board_read'))

                    <li class="sidebar-menu-item {{ set_menu(['communication/*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-user-friends"></i>
                            <span class="on-half-expanded">{{ ___('common.communication') }}</span>
                        </a>
                        <ul class="child-menu-list">
                            <li class="sidebar-menu-item {{ set_menu(['communication/notice*']) }}">
                                <a href="{{ route('notice-board.index') }}">{{ ___('common.notice_board') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if (
                    (hasPermission('role_read') ||
                        hasPermission('user_read') ||
                        hasPermission('department_read') ||
                        hasPermission('designation_read')) &&
                        hasFeature('staff_manage'))
                    <li class="sidebar-menu-item {{ set_menu(['users*', 'roles*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-user-friends"></i>
                            <span class="on-half-expanded">{{ ___('settings.staff_manage') }}</span>
                        </a>

                        <!-- second layer child menu list start  -->

                        <ul class="child-menu-list">
                            @if (hasPermission('role_read'))
                                <li class="sidebar-menu-item {{ set_menu(['roles*']) }}">
                                    <a href="{{ route('roles.index') }}">{{ ___('users_roles.roles') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('user_read'))
                                <li class="sidebar-menu-item {{ set_menu(['users*']) }}">
                                    <a href="{{ route('users.index') }}">{{ ___('settings.staff') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('department_read'))
                                <li
                                    class="sidebar-menu-item {{ set_menu(['department', 'department/create', 'department/edit*']) }}">
                                    <a href="{{ route('department.index') }}">{{ ___('staff.department') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('designation_read'))
                                <li class="sidebar-menu-item {{ set_menu(['designation*']) }}">
                                    <a href="{{ route('designation.index') }}">{{ ___('staff.designation') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (hasPermission('contact_message_read'))
                    <li class="sidebar-menu-item {{ set_menu(['contact-message*']) }}">
                        <a href="{{ route('contact-message.index') }}" class="parent-item-content">
                            <i class="las la-sms"></i>
                            <span class="on-half-expanded">{{ ___('settings.contact_message') }}</span>
                        </a>
                    </li>
                @endif
                <!-- Subscription layout end -->


                <!-- Website setup start -->
                @if (
                    (hasPermission('page_sections_read') ||
                        hasPermission('slider_read') ||
                        hasPermission('about_read') ||
                        hasPermission('counter_read') ||
                        hasPermission('contact_info_read') ||
                        hasPermission('dep_contact_read') ||
                        hasPermission('news_read') ||
                        hasPermission('event_read')) &&
                        hasFeature('website_setup'))
                    <li
                        class="sidebar-menu-item {{ set_menu(['page-sections*', 'slider*', 'about*', 'counter*', 'contact-info*', 'department-contact*', 'admin-news*', 'event*', 'gallery-category*', 'gallery', 'gallery/*']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-wrench"></i>
                            <span class="on-half-expanded">{{ ___('settings.Website_setup') }}</span>
                        </a>

                        <!-- second layer child menu list start  -->

                        <ul class="child-menu-list">
                            @if (hasPermission('page_sections_read'))
                                <li class="sidebar-menu-item {{ set_menu(['page*']) }}">
                                    <a href="{{ route('page.index') }}">{{ ___('settings.pages') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('page_sections_read'))
                                <li class="sidebar-menu-item {{ set_menu(['page-sections*']) }}">
                                    <a href="{{ route('sections.index') }}">{{ ___('settings.sections') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('slider_read'))
                                <li class="sidebar-menu-item {{ set_menu(['slider*']) }}">
                                    <a href="{{ route('slider.index') }}">{{ ___('settings.Slider') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('page_sections_read') || hasPermission('gallery_read'))
                                <li class="sidebar-menu-item {{ set_menu(['gallery', 'gallery/*']) }}">
                                    <a href="{{ route('gallery.index') }}">{{ ___('settings.Images') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('page_sections_read') || hasPermission('gallery_category_read'))
                                <li class="sidebar-menu-item {{ set_menu(['gallery-category*']) }}">
                                    <a href="{{ route('gallery-category.index') }}">{{ ___('settings.Gallery_category') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('about_read'))
                                <li class="sidebar-menu-item {{ set_menu(['about*']) }}">
                                    <a href="{{ route('about.index') }}">{{ ___('settings.about') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('counter_read'))
                                <li class="sidebar-menu-item {{ set_menu(['counter*']) }}">
                                    <a href="{{ route('counter.index') }}">{{ ___('settings.Counter') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('contact_info_read'))
                                <li class="sidebar-menu-item {{ set_menu(['contact-info*']) }}">
                                    <a
                                        href="{{ route('contact-info.index') }}">{{ ___('settings.contact_information') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('dep_contact_read'))
                                <li class="sidebar-menu-item {{ set_menu(['department-contact*']) }}">
                                    <a
                                        href="{{ route('department-contact.index') }}">{{ ___('settings.department_contact') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('news_read'))
                                <li class="sidebar-menu-item {{ set_menu(['admin-news*']) }}">
                                    <a href="{{ route('news.index') }}">{{ ___('settings.News') }}</a>
                                </li>
                            @endif
                            @if (hasPermission('event_read'))
                                <li class="sidebar-menu-item {{ set_menu(['event*']) }}">
                                    <a href="{{ route('event.index') }}">{{ ___('settings.event') }}</a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif
                <!-- Website setup end -->



                <!-- Settings layout start -->
                @if (
                    (hasPermission('general_settings_read') ||
                        hasPermission('storage_settings_read') ||
                        hasPermission('task_schedules_read') ||
                        hasPermission('recaptcha_settings_read') ||
                        hasPermission('email_settings_read') ||
                        hasPermission('sms_settings_read') ||
                        hasPermission('gender_read') ||
                        hasPermission('religion_read') ||
                        hasPermission('blood_group_read') ||
                        hasPermission('session_read')) &&
                        hasFeature('setting'))

                    <li class="sidebar-menu-item {{ set_menu(['setting*', 'genders*', 'religions*']) }}">
                        <a href="#" class="parent-item-content has-arrow">
                            <i class="las la-cog"></i>
                            <span class="on-half-expanded">{{ ___('common.settings') }}</span>
                        </a>

                        <!-- second layer child menu list start  -->
                        <ul class="child-menu-list">
                            @if (hasPermission('general_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.general-settings']) }}">
                                    <a
                                        href="{{ route('settings.general-settings') }}">{{ ___('settings.general_settings') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('storage_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.storagesetting']) }}">
                                    <a
                                        href="{{ route('settings.storagesetting') }}">{{ ___('settings.storage_settings') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('task_schedules_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.task-schedulers']) }}">
                                    <a
                                        href="{{ route('settings.task-schedulers') }}">{{ ___('settings.task_schedules') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('recaptcha_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.recaptcha-setting']) }}">
                                    <a
                                        href="{{ route('settings.recaptcha-setting') }}">{{ ___('settings.recaptcha_settings') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('sms_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.recaptcha-setting']) }}">
                                    <a
                                        href="{{ route('settings.sms-setting') }}">{{ ___('settings.sms_settings') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('email_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.mail-setting']) }}">
                                    <a
                                        href="{{ route('settings.mail-setting') }}">{{ ___('settings.email_settings') }}</a>
                                </li>
                            @endif

                            @if (hasPermission('email_settings_read'))
                                <li class="sidebar-menu-item {{ set_menu(['settings.notification-settings']) }}">
                                    <a
                                        href="{{ route('settings.notification-settings') }}">{{ ___('settings.notification_setting') }}</a>
                                </li>
                            @endif

                            <!-- gender layout start -->
                            @if (hasPermission('gender_read'))
                                <li class="sidebar-menu-item {{ set_menu(['genders*']) }}">
                                    <a href="{{ route('genders.index') }}">{{ ___('settings.genders') }}</a>
                                </li>
                            @endif
                            <!-- gender layout end -->

                            <!-- religion layout start -->
                            @if (hasPermission('religion_read'))
                                <li class="sidebar-menu-item {{ set_menu(['religions*']) }}">
                                    <a href="{{ route('religions.index') }}">{{ ___('settings.religions') }}</a>
                                </li>
                            @endif
                            <!-- religion layout end -->

                            <!-- blood_group layout start -->
                            @if (hasPermission('blood_group_read'))
                                <li class="sidebar-menu-item {{ set_menu(['blood-groups*']) }}">
                                    <a
                                        href="{{ route('blood-groups.index') }}">{{ ___('settings.blood_groups') }}</a>
                                </li>
                            @endif
                            <!-- blood_group layout end -->

                            <!-- session layout start -->
                            @if (hasPermission('session_read'))
                                <li class="sidebar-menu-item {{ set_menu(['sessions*']) }}">
                                    <a href="{{ route('sessions.index') }}">{{ ___('settings.sessions') }}</a>
                                </li>
                            @endif
                            <!-- session layout end -->
                        </ul>
                        <!-- second layer child menu list end  -->
                    </li>
                @endif
                <!-- Settings layout end -->


                <!-- Components Layout End -->
            </ul>
            <!-- parent menu list end  -->


        </div>


    </div>
</aside>
