export type UserRole = 'student' | 'admin'

export type UserSession = {
  email: string
  name: string
  role: UserRole
}

export type Room = {
  id: string
  name: string
  location: string
  capacity: number
  features: string[]
  active: boolean
  createdAt: string
  updatedAt: string
  imageUrl?: string
}

export type CreateRoomInput = {
  name: string
  location: string
  capacity: number
  features: string[]
  active: boolean
}

export type BookingStatus = 'pending' | 'approved' | 'confirmed' | 'cancelled' | 'rejected' | 'completed'

export type Booking = {
  id: string
  roomId: string
  userName: string
  userRole: UserRole
  date: string
  startTime: string
  endTime: string
  purpose: string
  status: BookingStatus
  createdAt: string
  updatedAt: string
}

export type CreateBookingInput = {
  roomId: string
  userName: string
  userRole: UserRole
  date: string
  startTime: string
  endTime: string
  purpose: string
}
