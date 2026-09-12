import type { User } from "./auth";

export type Category = {
    id: number;
    name: string;
    slug: string;
    courses_count?: number;
};

export type CourseStatus = "draft" | "published" | "archived";

export type Course = {
    id: number;
    instructor_id: number;
    category_id: number | null;
    title: string;
    slug: string;
    description: string | null;
    cover_path: string | null;
    price: string;
    status: CourseStatus;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    instructor?: Pick<User, "id" | "name">;
    category?: Category | null;
    modules?: Module[];
    lessons_count?: number;
    can?: {
        update: boolean;
        delete: boolean;
    };
};

export type Module = {
    id: number;
    course_id: number;
    title: string;
    position: number;
    lessons?: Lesson[];
};

export type Lesson = {
    id: number;
    module_id: number;
    title: string;
    content: string | null;
    video_url: string | null;
    duration_minutes: number | null;
    is_free_preview: boolean;
    position: number;
};

export type EnrollmentStatus = "active" | "completed" | "cancelled";

export type Enrollment = {
    id: number;
    user_id: number;
    course_id: number;
    status: EnrollmentStatus;
    progress_percent: number;
    enrolled_at: string;
    completed_at: string | null;
    course?: Course;
};

export type PaymentStatus = "pending" | "confirmed" | "rejected";

export type PaymentMethod = "cash" | "bank_transfer" | "other";

export type Payment = {
    id: number;
    user_id: number;
    course_id: number;
    amount: string;
    currency: string;
    method: PaymentMethod;
    status: PaymentStatus;
    confirmed_by: number | null;
    confirmed_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    user?: Pick<User, "id" | "name" | "email">;
    course?: Pick<Course, "id" | "title" | "slug">;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLink[];
};
