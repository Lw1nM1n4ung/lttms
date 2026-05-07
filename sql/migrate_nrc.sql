-- Migration: Add NRC fields to users table
ALTER TABLE users
    ADD COLUMN nrc_number VARCHAR(50) NULL AFTER phone,
    ADD COLUMN nrc_front_photo VARCHAR(255) NULL AFTER nrc_number,
    ADD COLUMN nrc_back_photo VARCHAR(255) NULL AFTER nrc_front_photo;
