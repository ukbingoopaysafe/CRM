# مخطط وتفاصيل قاعدة البيانات (Database Schema Documentation)

توثق هذه الصفحة المخطط التفصيلي لجداول وعلاقات قاعدة بيانات نظام CRM العقاري المتخصص في عمليات الإيجار والريسيل.

---

## 1. الجداول المركزية المشتركة (Shared Core Tables)

### جدول المستخدمين والصلاحيات (`users`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف الأساسي |
| `name` | varchar | الاسم الكامل للموظف أو المدير |
| `email` | varchar (unique) | البريد الإلكتروني لتسجيل الدخول |
| `password` | varchar | كلمة المرور المشفرة |
| `role` | enum | الدور: `admin` (مدير), `rental_agent` (موظف إيجار), `resale_agent` (موظف ريسيل) |
| `is_active` | boolean | حالة الحساب (نشط / معطل) |
| `timestamps` | timestamp | تاريخ الإنشاء والتحديث |

---

### جدول العملاء المركزي (`clients`)
* **الهدف:** سجل موحد للعملاء في شركتك لمنع التكرار، وربط كافة طلبات الإيجار والشراء لنفس الشخص.
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | كود العميل |
| `name` | varchar | اسم العميل الكامل |
| `phone` | varchar | رقم الهاتف الأصلي كما أدخله الموظف |
| `normalized_phone` | varchar (indexed) | رقم الهاتف بعد إزالة المسافات وتوحيد الصيغة للبحث ومنع التكرار |
| `email` | varchar (nullable) | البريد الإلكتروني |
| `nationality` | varchar (nullable) | الجنسية |
| `notes` | text (nullable) | ملاحظات عامة حول العميل |
| `created_by` | foreignId (users) | الموظف الذي أنشأ السجل |
| `timestamps` & `softDeletes` | timestamp | تتبع التواريخ والحذف الآمن |

---

### جدول الوسطاء والشركاء (`brokers`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | كود الوسيط |
| `name` | varchar | اسم الوسيط أو الشركة الشريكة |
| `phone` | varchar | رقم هاتف التواصل |
| `company_name` | varchar (nullable) | اسم شركة الوساطة إن وجدت |
| `notes` | text (nullable) | ملاحظات حول نسبة العمولة المعتادة وطرق التعاون |

---

## 2. جداول قسم الإيجارات (Rentals Tables)

### جدول طلبات الإيجار (`rental_requests`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | رقم الطلب |
| `client_id` | foreignId (clients) (nullable) | العميل المرتبط (يمكن أن يكون فارغاً إذا كان الطلب قادماً عبر وسيط) |
| `broker_id` | foreignId (brokers) (nullable) | الوسيط المرتبط بالطلب |
| `assigned_to` | foreignId (users) | الموظف المسؤول عن متابعة الطلب |
| `source` | varchar | مصدر الطلب (فيسبوك، واتساب، ترشيح صديق، موقع الشركة) |
| `status` | enum | حالة الطلب: `جديد`, `نشط`, `تم التواصل`, `جارٍ البحث`, `تم إرسال خيارات`, `معاينة مجدولة`, `قيد التفاوض`, `معلق`, `مغلق بنجاح`, `مغلق دون نجاح` |
| `family_status` | varchar (nullable) | حالة الأسرة وعدد الأفراد |
| `notes` | text (nullable) | ملاحظات تشغيلية |

### جدول متطلبات وميزانية الإيجار (`rental_requirements`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `rental_request_id` | foreignId (unique) | مرتبط بطلب إيجار واحد |
| `property_type` | varchar | نوع العقار (شقة، فيلا، دوبلكس، بنتهاوس) |
| `bedrooms` | integer (nullable) | عدد الغرف المطلوبة |
| `furnishing_status` | enum | حالة الفرش: مفروش، غير مفروش، أجهزة ومطبخ فقط |
| `preferred_locations` | text | المناطق والكمباوندات المفضلة كنص مرن |
| `rental_duration` | varchar | مدة الإيجار (سنة، شهور، تجديد سنوي) |
| `move_in_date` | date (nullable) | التاريخ المستهدف للانتقال |
| `stated_budget_min` | decimal | الحد الأدنى للميزانية المعلنة من العميل |
| `stated_budget_max` | decimal | الحد الأقصى للميزانية المعلنة من العميل |
| `qualified_budget_min` | decimal (nullable) | الحد الأدنى للميزانية الواقعية بعد مناقشة السوق |
| `qualified_budget_max` | decimal (nullable) | الحد الأقصى للميزانية الواقعية بعد مناقشة السوق |
| `currency` | varchar (default: EGP) | العملة |

---

## 3. جداول قسم الريسيل (Resale Tables)

### جدول طلبات الريسيل (`resale_requests`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | رقم الطلب |
| `client_id` | foreignId (clients) | العميل المشتري أو المستثمر |
| `broker_id` | foreignId (brokers) (nullable) | وسيط شريك إن وجد |
| `assigned_to` | foreignId (users) | موظف المبيعات/الريسيل المسؤول |
| `status` | enum | حالات الطلب: `جديد`, `تأهيل مالي`, `ترشيح مشروعات`, `معاينة موقع`, `تفاوض وعرض سعر`, `حجز وتوقيع`, `مغلق بنجاح`, `مغلق دون نجاح` |
| `purpose` | enum | الغرض: `سكن شخصي`, `استثمار وإعادة بيع`, `استثمار للإيجار` |

### جدول متطلبات الشراء والريسيل (`resale_requirements`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `resale_request_id` | foreignId (unique) | مرتبط بطلب ريسيل واحد |
| `property_type` | varchar | نوع العقار المطلوب |
| `preferred_locations` | text | المناطق والكمباوندات المحددة |
| `payment_method` | enum | طريقة السداد: `كاش فوري`, `تقسيط وتسهيلات`, `كلاهما متاح` |
| `total_budget` | decimal | إجمالي الميزانية المستهدفة |
| `down_payment_max` | decimal (nullable) | أقصى مقدم كاش مستعد لدفعه العميل |
| `installment_capacity` | decimal (nullable) | القسط الشهري أو الربع سنوي الذي يتحمله العميل |
| `installment_years` | integer (nullable) | سنوات التقسيط المفضلة |
| `delivery_target` | enum | موعد الاستلام: `فوري`, `خلال سنة`, `تحت الإنشاء` |

---

## 4. المحركات التشغيلية المشتركة (Operational Subsystems)

### جدول ترشيحات الوحدات (`unit_nominations`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `request_type` | enum | نوع الطلب: `rental` أو `resale` |
| `request_id` | bigint | معرف طلب الإيجار أو الريسيل |
| `unit_code` | varchar (nullable) | كود أو مرجع الوحدة الداخلي |
| `title` | varchar | عنوان أو ملخص الوحدة (مثال: شقة 180م بكمباوند ميفيدا) |
| `location` | varchar | الموقع أو الكمباوند |
| `price` | decimal | السعر المطلوب (قيمة الإيجار أو إجمالي سعر البيع) |
| `media_url` | text (nullable) | رابط خارجي للصور أو الفيديو (Drive / Cloud) |
| `client_reaction` | enum | استجابة العميل: `قيد الانتظار`, `أبدى اهتماماً`, `مرفوضة من العميل` |
| `reaction_notes` | text (nullable) | ملاحظات العميل حول الوحدة |

### جدول فحص الوحدات (`unit_inspections`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `unit_nomination_id` | foreignId | مرتبط بترشيح الوحدة المحدد |
| `inspected_by` | foreignId (users) | الموظف الذي أجرى الفحص الميداني |
| `inspected_at` | datetime | موعد الفحص |
| `result` | enum | النتيجة الحاسمة: `suitable` (مناسبة) أو `unsuitable` (غير مناسبة) |
| `is_matched_reality` | boolean | هل تطابق الصور والبيانات مع الواقع؟ |
| `condition_rating` | varchar | حالة التشطيب والمرافق |
| `rejection_reason` | text (nullable) | **إلزامي في حال كانت غير مناسبة** لتوضيح السبب |

### جدول معاينات العملاء (`client_showings`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `unit_nomination_id` | foreignId | ترشيح الوحدة المعاينة |
| `scheduled_at` | datetime | موعد المعاينة المحدد |
| `assigned_to` | foreignId (users) | الموظف المرافق للعميل |
| `status` | enum | الحالة: `مجدولة`, `مؤكدة`, `مكتملة`, `عدم حضور العميل`, `ملغاة`, `معاد جدولتها` |
| `client_feedback` | text (nullable) | رد واعتراضات العميل بعد المعاينة الميدانية |

### جدول نتائج الصفقات والعمولة (`deal_outcomes`)
| الحقل | النوع | الوصف |
| :--- | :--- | :--- |
| `id` | bigint (PK) | المعرف |
| `request_type` | enum | نوع الصفقة: `rental` أو `resale` |
| `request_id` | bigint | رقم الطلب المغلق |
| `outcome` | enum | النتيجة النهائية: `won` (صفقة ناجحة) أو `lost` (صفقة غير ناجحة) |
| `final_price` | decimal (nullable) | القيمة النهائية التي تم الاتفاق عليها |
| `commission_amount` | decimal (nullable) | قيمة عمولة الشركة المحققة |
| `broker_commission` | decimal (nullable) | عمولة الوسيط الشريك إن وجدت |
| `loss_reason` | varchar (nullable) | سبب الخسارة في حال عدم النجاح (الميزانية، الموقع، تراجع العميل، إلخ) |
| `closed_by` | foreignId (users) | المستخدم الذي أغلق الصفقة |
| `closed_at` | datetime | تاريخ الإغلاق |
