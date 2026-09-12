CREATE TABLE categories (
    id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    name    VARCHAR(100) NOT NULL,
    slug    VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE courses (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    instructor_id   BIGINT        NOT NULL,
    category_id     BIGINT        NULL,
    title           VARCHAR(180)  NOT NULL,
    slug            VARCHAR(200)  NOT NULL UNIQUE,
    description     TEXT          NULL,
    cover_path      VARCHAR(255)  NULL,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_at    TIMESTAMP     NULL,
    created_at      TIMESTAMP     NULL,
    updated_at      TIMESTAMP     NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (category_id)   REFERENCES categories(id) ON DELETE SET NULL
);

-- Sections/modules inside a course
CREATE TABLE modules (
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id   BIGINT       NOT NULL,
    title       VARCHAR(180) NOT NULL,
    position    SMALLINT     NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_module_position (course_id, position)
);

CREATE TABLE lessons (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    module_id       BIGINT       NOT NULL,
    title           VARCHAR(180) NOT NULL,
    content         LONGTEXT     NULL,          -- HTML / markdown
    video_url       VARCHAR(255) NULL,
    duration_minutes SMALLINT    NULL,
    is_free_preview BOOLEAN      NOT NULL DEFAULT FALSE,
    position        SMALLINT     NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NULL,
    updated_at      TIMESTAMP    NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Student <-> course relationship
CREATE TABLE enrollments (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT    NOT NULL,
    course_id       BIGINT    NOT NULL,
    status          ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    progress_percent TINYINT  NOT NULL DEFAULT 0,   -- denormalized, for fast listings
    enrolled_at     TIMESTAMP NOT NULL,
    completed_at    TIMESTAMP NULL,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_enrollment (user_id, course_id)
);

CREATE INDEX idx_courses_status     ON courses(status, published_at);
CREATE INDEX idx_enrollments_user   ON enrollments(user_id, status);
CREATE INDEX idx_lessons_module_pos ON lessons(module_id, position);