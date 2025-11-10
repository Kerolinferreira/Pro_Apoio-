import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '../../tests/utils/test-utils'
import { fireEvent } from '@testing-library/react'
import {
  LoadingSpinner,
  ErrorAlert,
  Button,
  Input,
  Select,
  CheckboxGroup
} from './index'

describe('UI Components', () => {
  describe('LoadingSpinner', () => {
    it('renders loading text', () => {
      render(<LoadingSpinner />)
      expect(screen.getByText('Carregando...')).toBeInTheDocument()
    })

    it('has correct CSS classes', () => {
      const { container } = render(<LoadingSpinner />)
      const element = container.firstChild as HTMLElement
      expect(element.className).toContain('text-center')
      expect(element.className).toContain('py-10')
    })
  })

  describe('ErrorAlert', () => {
    it('renders error message', () => {
      render(<ErrorAlert message="Error occurred" />)
      expect(screen.getByText('Error occurred')).toBeInTheDocument()
    })

    it('has error styling', () => {
      const { container } = render(<ErrorAlert message="Test error" />)
      const element = container.firstChild as HTMLElement
      expect(element.className).toContain('text-red-600')
    })
  })

  describe('Button', () => {
    it('renders button text', () => {
      render(<Button>Click me</Button>)
      expect(screen.getByText('Click me')).toBeInTheDocument()
    })

    it('handles click events', () => {
      const handleClick = vi.fn()
      render(<Button onClick={handleClick}>Click me</Button>)

      fireEvent.click(screen.getByText('Click me'))
      expect(handleClick).toHaveBeenCalledTimes(1)
    })

    it('can be disabled', () => {
      render(<Button disabled>Disabled</Button>)
      const button = screen.getByText('Disabled')
      expect(button).toBeDisabled()
    })

    it('passes through HTML attributes', () => {
      render(<Button type="submit">Submit</Button>)
      const button = screen.getByText('Submit')
      expect(button).toHaveAttribute('type', 'submit')
    })
  })

  describe('Input', () => {
    it('renders input field', () => {
      render(<Input placeholder="Enter text" />)
      expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument()
    })

    it('renders with label', () => {
      render(<Input label="Username" />)
      expect(screen.getByText('Username')).toBeInTheDocument()
    })

    it('handles value changes', () => {
      const handleChange = vi.fn()
      render(<Input onChange={handleChange} />)

      const input = screen.getByRole('textbox')
      fireEvent.change(input, { target: { value: 'test' } })

      expect(handleChange).toHaveBeenCalled()
    })

    it('can have different types', () => {
      render(<Input type="email" placeholder="Email" />)
      const input = screen.getByPlaceholderText('Email')
      expect(input).toHaveAttribute('type', 'email')
    })
  })

  describe('Select', () => {
    const options = [
      { value: '1', label: 'Option 1' },
      { value: '2', label: 'Option 2' },
      { value: '3', label: 'Option 3' }
    ]

    it('renders select with options', () => {
      render(<Select options={options} />)
      expect(screen.getByRole('combobox')).toBeInTheDocument()
      expect(screen.getByText('Option 1')).toBeInTheDocument()
      expect(screen.getByText('Option 2')).toBeInTheDocument()
      expect(screen.getByText('Option 3')).toBeInTheDocument()
    })

    it('renders with label', () => {
      render(<Select label="Choose option" options={options} />)
      expect(screen.getByText('Choose option')).toBeInTheDocument()
    })

    it('handles selection changes', () => {
      const handleChange = vi.fn()
      render(<Select options={options} onChange={handleChange} />)

      const select = screen.getByRole('combobox')
      fireEvent.change(select, { target: { value: '2' } })

      expect(handleChange).toHaveBeenCalled()
    })
  })

  describe('CheckboxGroup', () => {
    const options = [
      { value: 'opt1', label: 'Option 1' },
      { value: 'opt2', label: 'Option 2' },
      { value: 'opt3', label: 'Option 3' }
    ]

    it('renders all checkboxes', () => {
      const handleChange = vi.fn()
      render(
        <CheckboxGroup
          label="Select options"
          options={options}
          selectedValues={[]}
          onChange={handleChange}
        />
      )

      expect(screen.getByText('Select options')).toBeInTheDocument()
      expect(screen.getByText('Option 1')).toBeInTheDocument()
      expect(screen.getByText('Option 2')).toBeInTheDocument()
      expect(screen.getByText('Option 3')).toBeInTheDocument()
    })

    it('shows selected values as checked', () => {
      const handleChange = vi.fn()
      render(
        <CheckboxGroup
          label="Select options"
          options={options}
          selectedValues={['opt1', 'opt3']}
          onChange={handleChange}
        />
      )

      const checkboxes = screen.getAllByRole('checkbox')
      expect(checkboxes[0]).toBeChecked()
      expect(checkboxes[1]).not.toBeChecked()
      expect(checkboxes[2]).toBeChecked()
    })

    it('calls onChange when checkbox is clicked', () => {
      const handleChange = vi.fn()
      render(
        <CheckboxGroup
          label="Select options"
          options={options}
          selectedValues={['opt1']}
          onChange={handleChange}
        />
      )

      const checkbox = screen.getByLabelText('Option 2')
      fireEvent.click(checkbox)

      expect(handleChange).toHaveBeenCalledWith(['opt1', 'opt2'])
    })

    it('removes value when unchecking', () => {
      const handleChange = vi.fn()
      render(
        <CheckboxGroup
          label="Select options"
          options={options}
          selectedValues={['opt1', 'opt2']}
          onChange={handleChange}
        />
      )

      const checkbox = screen.getByLabelText('Option 1')
      fireEvent.click(checkbox)

      expect(handleChange).toHaveBeenCalledWith(['opt2'])
    })
  })
})
