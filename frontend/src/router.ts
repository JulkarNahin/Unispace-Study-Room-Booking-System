import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './views/HomePage.vue'
import StudentLoginPage from './views/student/StudentLoginPage.vue'
import StudentDashboardPage from './views/student/StudentDashboardPage.vue'
import StudyRoomsPage from './views/student/StudyRoomsPage.vue'
import ReserveRoomPage from './views/student/ReserveRoomPage.vue'
import ReserveRoomIndexPage from './views/student/ReserveRoomIndexPage.vue'
import MyReservationsPage from './views/student/MyReservationsPage.vue'
import ReservationHistoryPage from './views/student/ReservationHistoryPage.vue'
import StudentReportsPage from './views/student/StudentReportsPage.vue'

import AdminLoginPage from './views/admin/AdminLoginPage.vue'
import AdminDashboardPage from './views/admin/AdminDashboardPage.vue'
import AdminManageReservationsPage from './views/admin/AdminManageReservationsPage.vue'
import AdminManageRoomsPage from './views/admin/AdminManageRoomsPage.vue'
import AdminManageStudentsPage from './views/admin/AdminManageStudentsPage.vue'
import AdminReportsPage from './views/admin/AdminReportsPage.vue'
import NotFoundPage from './views/NotFoundPage.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/student/login', name: 'student-login', component: StudentLoginPage },
    { path: '/student/dashboard', name: 'student-dashboard', component: StudentDashboardPage },
    { path: '/student/rooms', name: 'student-rooms', component: StudyRoomsPage },
    { path: '/student/reserve', name: 'student-reserve-index', component: ReserveRoomIndexPage },
    { path: '/student/reserve/:id', name: 'student-reserve', component: ReserveRoomPage },
    { path: '/student/reservations', name: 'student-reservations', component: MyReservationsPage },
    { path: '/student/history', name: 'student-history', component: ReservationHistoryPage },
    { path: '/student/reports', name: 'student-reports', component: StudentReportsPage },

    { path: '/admin/login', name: 'admin-login', component: AdminLoginPage },
    { path: '/admin/dashboard', name: 'admin-dashboard', component: AdminDashboardPage },
    { path: '/admin/reservations', name: 'admin-reservations', component: AdminManageReservationsPage },
    { path: '/admin/rooms', name: 'admin-rooms', component: AdminManageRoomsPage },
    { path: '/admin/students', name: 'admin-students', component: AdminManageStudentsPage },
    { path: '/admin/reports', name: 'admin-reports', component: AdminReportsPage },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})
