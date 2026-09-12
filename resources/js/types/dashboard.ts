import type { Course, Enrollment, Payment } from "./courses";

export type StudentDashboardProps = {
    role: "student";
    stats: {
        active_courses: number;
        completed_courses: number;
        pending_payments: number;
    };
};

export type InstructorDashboardProps = {
    role: "instructor";
    stats: {
        published_courses: number;
        draft_courses: number;
        students: number;
    };
    courses: Course[];
    recent_enrollments: Enrollment[];
};

export type AdminDashboardProps = {
    role: "admin";
    stats: {
        users: number;
        courses: number;
        active_enrollments: number;
        confirmed_revenue: number;
        pending_payments: number;
    };
    pending_payments: Payment[];
};

export type DashboardProps =
    StudentDashboardProps | InstructorDashboardProps | AdminDashboardProps;
