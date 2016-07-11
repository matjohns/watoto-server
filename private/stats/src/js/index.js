import '../scss/index.scss'

import Moment from 'moment'

import React, { Component } from 'react'
import ReactDOM from 'react-dom'
import App from 'grommet/components/App'
import Header from 'grommet/components/Header'
import Anchor from 'grommet/components/Anchor'
import Menu from 'grommet/components/Menu'
import Title from 'grommet/components/Title'

import Users from './components/Users'
import Drugs from './components/Drugs'
import Countries from './components/Countries'
import Devices from './components/Devices'

const Refresh = require('grommet/components/icons/base/Refresh')

const Main = React.createClass({
  getInitialState() {
    return {
      data: undefined,
    }
  },

  componentWillMount() {
    this.reload()
  },

  reload() {
    fetch('/private/api/data', {
      mode: 'no-cors',
      credentials: 'include',
    })
      .then(resp => resp.json())
      .then(data => {
        this.state.data = data
        this.forceUpdate()
      })
  },

  render() {
    return (
      <App centered={true}>
        <Header justify='between' colorIndex='neutral-1' pad={{horizontal:'medium'}}>
          <Title>Watoto Stats</Title>
          <Menu direction='row' align='center' responsive={false}>
            <Anchor href='#' icon={<Refresh />} label='Refresh' onClick={this.reload} />
          </Menu>
        </Header>
        <Users data={this.state.data} />
        <Countries data={this.state.data} />
        <Drugs data={this.state.data} />
        <Devices data={this.state.data} />
      </App>
    )
  },
})

let element = document.getElementById('content')
ReactDOM.render(React.createElement(Main), element)

document.body.classList.remove('loading')
